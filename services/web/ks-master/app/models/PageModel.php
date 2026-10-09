<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * app/models/PageModel.php
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
*/

class PageModel extends Model {
	
	// SMS: levikkikartan tietojen haku
	public function getDistributionMapData($id) {
		
		try {
			
			// Get SQL handle.
			$sql = $this->getSQLHandle();
			
			// Build a query to get all distribution data.
			$query = $sql->prepare("SELECT hakusana, homonyyminro, sanaluokka, levikkityyppi, selite, muoto FROM kohde WHERE kohde_id = ?");
			
			// Bind id to query.
			$query->bindValue(1, $id);
			
			// Set fetch mode to object and execute query.
			$query->setFetchMode(PDO::FETCH_OBJ);
			$query->execute();
			
			// Return resultset.
			return $query->fetch();
			
		} catch (QuerySQLException $e) {
			
		} catch (DICTException $e) {
			
		}
		
	}

}

?>
