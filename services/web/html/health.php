<?php
// ALB target group health check hits this path (infra/bin/infra.ts,
// WebEcsService healthCheckPath) - kept independent of eXist-db reachability
// so a backend hiccup doesn't flap the whole web service's health status.
header('Content-Type: text/plain');
echo "ok\n";
