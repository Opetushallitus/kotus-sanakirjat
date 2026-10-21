# Playbook: PHP security update

How to move the web image onto a new PHP release, both for routine patch
updates and for urgent security advisories. Applies to the `sanakirja-web`
image only. The eXist-db image is Java and does not contain PHP.

## Where the PHP version comes from

The PHP version is set by a single line in `services/web/Dockerfile`:

```dockerfile
FROM php:8.3.36-apache
```

It is always pinned to an **exact patch release** (`8.3.36`), never a floating
tag like `8.3-apache`. With an exact pin, the running version is visible in git
history, and a rebuild can never quietly land on a different PHP version. A
PHP update is therefore always a one-line change to this file, followed by the
normal build and deploy pipeline.

Every environment serves the same image, so all of them have to be updated.
That includes environments that are not in use yet: they are still reachable
from the internet.

## Routine update (no active threat)

Do this when a new patch release of the pinned minor version comes out (PHP
publishes them roughly monthly).

1. Check the latest release of the minor version:
   `curl -s 'https://www.php.net/releases/index.php?json&version=8.3'`
2. Read the changelog (<https://www.php.net/ChangeLog-8.php>) for anything that
   affects the extensions this image installs (`pdo_pgsql`, `gettext`, `soap`)
   or the app's behaviour.
3. Check that the matching image exists on Docker Hub:
   `curl -s https://hub.docker.com/v2/repositories/library/php/tags/8.3.NN-apache`
   (it returns a `last_updated` timestamp when the tag exists).
4. Update the `FROM` line, then build and deploy through the normal pipeline
   (see the steps under "Urgent update" below, minus the time pressure).

## Urgent update (critical advisory)

### 1. Assess, before the fix is out

- **Confirm the advisory.** Use the PHP announcement list
  (<https://news-web.php.net/php.announce>) and the security advisories on
  <https://github.com/php/php-src/security>. Note the CVE ids, the affected
  versions and the release date/time (UTC).
- **Check which environments are exposed.** Each environment advertises its PHP
  version in a response header:

  ```sh
  for e in dev qa prod; do
    echo "== $e"; curl -sI https://alb.$e.<domain>/ | grep -i x-powered-by
  done
  ```

  Do not assume an environment is "not deployed". Check every one, because some
  may have been deployed from a local machine and won't appear in the GitHub
  Actions run history.
- **Look for a stopgap**, but check what it would break first. Disabling
  `file_uploads` or blocking `multipart/form-data` at the ALB both break admin
  features that rely on uploads, so neither is an acceptable stopgap here.
  Scaling a non-production service to 0 tasks
  (`aws ecs update-service --desired-count 0`) is fine for an environment
  nobody uses yet.

### 2. Wait for the official image

PHP's release (php.net) usually reaches the Docker official image (`php:*`)
a few hours later. Poll until both are out:

```sh
curl -s 'https://www.php.net/releases/index.php?json&version=8.3' | jq -r .version
curl -s https://hub.docker.com/v2/repositories/library/php/tags/8.3.NN-apache | jq -r .last_updated
```

Then check that the new version's changelog entry actually lists the
advisory's fix (GHSA/CVE ids) before relying on it.

### 3. Pin, commit, build

1. `git pull --rebase` (others push to this repo too).
2. Change the `FROM` line in `services/web/Dockerfile` to the patched tag.
   Mention the CVE ids in the comment above it and in the commit message.
3. Commit and push to `main`. Any change under `services/web/**` triggers
   `build-and-push.yml`, which builds both images under one `ga-<run number>`
   tag:

   ```sh
   gh run list --workflow build-and-push.yml -L 1
   gh run watch <run id> --exit-status
   ```

   A local `docker build` is not needed. The pipeline build is what gets
   deployed.

### 4. Deploy every environment, one at a time

Deploy the new web tag. Pass eXist-db's **currently running** tag so the
eXist-db service is not restarted for no reason:

```sh
gh workflow run deploy-infra.yml -f environment=dev  -f web_revision=ga-N -f existdb_revision=<current existdb tag>
gh run watch <run id> --exit-status
# then qa, then prod - never in parallel
```

To find the eXist-db tag currently running in an environment:

```sh
aws ecs describe-task-definition --task-definition <existdb task def> \
  --query 'taskDefinition.containerDefinitions[0].image'
```

Order: start with the environment that carries real traffic, so the real
exposure closes first. After that, the rest in any order, one at a time.

### 5. Verify

For each environment:

```sh
curl -sI https://alb.<env>.<domain>/ | grep -i x-powered-by   # must show the new version
curl -s -o /dev/null -w '%{http_code}\n' https://alb.<env>.<domain>/health   # 200
```

Then open the front page and run a search to confirm the app still works.

### 6. Follow-up

- Watch the service's CloudWatch logs and alarms for a while after the
  deploy.
- If the advisory says the flaw was exploited in the wild before the fix,
  review the ALB access logs (S3 log bucket) from the exposure window for
  suspicious multipart POSTs or other indicators named in the advisory.

## Rollback

If the new image breaks the app, redeploy the previous `ga-` tag with the
same `deploy-infra.yml` command. ECR tags are immutable, so old tags stay
available. Rolling back to a vulnerable version during an active threat is a
last resort. Prefer fixing forward.
