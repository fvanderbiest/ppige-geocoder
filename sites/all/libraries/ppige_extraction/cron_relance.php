<?php
	require_once("incphp/extract.php");
	
	$ini = parse_ini_file("incphp/config/config.ini");
	
	// R�cup�ration des extractions � relancer/supprimer
	$conn = dbConnect(); 
	$sql = "select 
				p.id_extraction,
				p.date_fin_traitement,
				p.id_contact,
				(select count(l.id_extraction) 
				   from extraction.log_mail_relance l 
				  where l.id_extraction=p.id_extraction) as total_relance 
			 from extraction.projet_extraction p 
			where p.etat_demande = 'TERMINEE' 
			  and p.fichier_supprimer is not true 
			  and p.date_fin_traitement is not null 
			  and p.date_fin_traitement < now() - interval '".$ini["nbJoursPremiereRelance"]." day'"; // Filtre sur les relances en fonction du d�lai
	
	$res = pg_query($conn, $sql);
	dbClose($conn);
	$currDate = new DateTime("NOW");

	// Parcourt les extractions
	while($row = pg_fetch_assoc($res)) {
		extract($row);
		writelog("================================================Extraction $id_extraction================================================");
		$log = "Extraction $id_extraction termin�e le $date_fin_traitement : $total_relance relance(s) - ";
		$date = new DateTime($date_fin_traitement);
		
		if($total_relance == 0) { // Premi�re relance
			$log .= "Lancement premi�re relance.";
			writelog($log);
			sendFirstRelance($id_extraction, $id_contact);
			insert_log_mail_relance($id_extraction);
		} elseif($total_relance < $ini["nbRelances"]) { // Relances suivantes
			$lastRelance = getLastRelance($id_extraction);
			$nextRelance = $lastRelance->add(new DateInterval("P".$ini["nbJoursRelances"]."D"));
			
			if($nextRelance <= $currDate) { // La relance est d�clench�e que si le d�lai entre les relances est respect�
				$log .= "Lancement de la ".($total_relance + 1)."� relance.";
				writelog($log);
				sendRelance($id_extraction, $id_contact, $total_relance + 1);
				insert_log_mail_relance($id_extraction);
			} else { // Si d�lai non respect�, rien � faire
				$log .= "Intervalle non �coul� entre les relances.";
				writelog($log);
				writelog("Derni�re : ".($lastRelance->format("Y-m-d")));
			}
		} else { // Cas suppression
			$lastRelance = getLastRelance($id_extraction);
			$nextRelance = $lastRelance->add(new DateInterval("P".$ini["nbJoursSuppression"]."D"));
			
			if($nextRelance <= $currDate) { // La suppression est d�clench�e que si le d�lai de suppression est respect�
				$log .= "Lancement de la suppression des fichiers.";
				writelog($log);
				insert_log_mail_relance($id_extraction, "SUPPRESSION");
				supprimerFichierExtraction($id_extraction);
				sendSuppression($id_extraction, $id_contact, $total_relance + 1);
			} else { // Sinon rien � faire
				$log .= "Intervalle non �coul� pour la suppression.";
				writelog($log);
				writelog("Derni�re : ".($lastRelance->format("Y-m-d")));
			}
		}		
	}
	
	/**
	*	Insertion des relances en base de donn�es
	**/
	function insert_log_mail_relance($id, $type = "RELANCE") {
		$conn = dbConnect(); 
		$sql = "INSERT INTO log_mail_relance(id_extraction, type) VALUES ($id, '$type')";
		writelog($sql);
		$res = pg_query($conn, $sql);
		dbClose($conn);
	}
	
	/**
	*	R�cup�ration de la derni�re relance
	**/
	function getLastRelance($id) {
		$conn = dbConnect(); 
		$sql = "SELECT * FROM log_mail_relance WHERE id_extraction = $id";
		writelog($sql);
		$res = pg_query($conn, $sql);
		$row = pg_fetch_assoc($res);
		dbClose($conn);
		return ($row && $row["date_action"]) ? new DateTime($row["date_action"]) : false;	
	}
	
	/**
	*	Envoie le mail de la premi�re relance
	**/
	function sendFirstRelance($id, $id_contact) {
		if($id < 277) return;
		$ini = parse_ini_file("incphp/config/config.ini");
		$mailObject = "Extraction $id";
		$contact = getContactInfo($id_contact);
		
		// Pour tests
		// if($id_contact != 2477) return;
		// $contact["email_contact"] = "t.boisteux@memoris.fr";
		
		$mailBody = "Bonjour ".$contact["libelle_contact"]."
		
Votre demande d'extraction $id a �t� tait�e il y a ".$ini["nbJoursPremiereRelance"]." jours ou plus.
Pour des raisons d'espace de stockage, nous sommes amen�s � supprimer les extractions au bout de 4 mois apr�s la commande. Pass� ce d�lai, nous ne garantissons plus que vous puissiez la r�cup�rer et vous devrez r�it�rer le processus d'extraction complet.

A ce jour, il vous reste ".(($ini["nbJoursRelances"] * ($ini["nbRelances"] - 1)) + $ini["nbJoursSuppression"])." jours pour la t�l�charger.

Si entre temps vous avez d�j� t�l�charg� les fichiers, merci de ne pas prendre en compte ce message.

Cordialement,
L'�quipe PPIGE
ppige@epf-npdc.fr
03 28 07 25 30
";
	writelog($mailBody);
	envoieMailWithSmtp($contact["email_contact"], $mailObject, $mailBody, $ini['fromMail']);
	}
	
	/**
	*	Envoie un mail de relance
	**/
	function sendRelance($id, $id_contact, $nbRelances) {
		if($id < 277) return;
		$ini = parse_ini_file("incphp/config/config.ini");
		$mailObject = "Extraction $id";
		$contact = getContactInfo($id_contact);
		
		// Pour tests
		// if($id_contact != 2477) return;
		// $contact["email_contact"] = "t.boisteux@memoris.fr";
		
		$mailBody = "Bonjour ".$contact["libelle_contact"]."
		
Votre demande d'extraction $id a �t� tait�e il y a ".($ini["nbJoursPremiereRelance"] +  (($nbRelances - 1) * $ini["nbJoursRelances"]))." jours ou plus.
Pour des raisons d'espace de stockage, nous sommes amen�s � supprimer les extractions au bout de 4 mois apr�s la commande. Pass� ce d�lai, nous ne garantissons plus que vous puissiez la r�cup�rer et vous devrez r�it�rer le processus d'extraction complet.

Votre extraction num�ro $id a �t� supprim�e aujourd'hui.

Si entre temps vous avez d�j� t�l�charg� les fichiers, merci de ne pas prendre en compte ce message.

Cordialement,
L'�quipe PPIGE
ppige@epf-npdc.fr
03 28 07 25 30
";
	writelog($mailBody);
	envoieMailWithSmtp($contact["email_contact"], $mailObject, $mailBody, $ini['fromMail']);
	}
	
	/**
	*	Envoie un mail d'information de suppression
	**/
	function sendSuppression($id, $id_contact, $nbRelances) {
		if($id < 277) return;
		$ini = parse_ini_file("incphp/config/config.ini");
		$mailObject = "Extraction $id";
		$contact = getContactInfo($id_contact);
		
		// Pour tests
		// if($id_contact != 2477) return;
		// $contact["email_contact"] = "t.boisteux@memoris.fr";
		
		$mailBody = "Bonjour ".$contact["libelle_contact"]."
		
Votre demande d'extraction $id a �t� tait�e il y a ".($ini["nbJoursPremiereRelance"] +  (($nbRelances - 1) * $ini["nbJoursRelances"]))." jours ou plus.
Pour des raisons d'espace de stockage, nous sommes amen�s � supprimer les extractions au bout de 4 mois apr�s la commande. Pass� ce d�lai, nous ne garantissons plus que vous puissiez la r�cup�rer et vous devrez r�it�rer le processus d'extraction complet.

A ce jour, il vous reste ".(($ini["nbJoursRelances"] * ($ini["nbRelances"] - $nbRelances)) + $ini["nbJoursSuppression"])." jours pour la t�l�charger.

Si entre temps vous avez d�j� t�l�charg� les fichiers, merci de ne pas prendre en compte ce message.

Cordialement,
L'�quipe PPIGE
ppige@epf-npdc.fr
03 28 07 25 30
";
	writelog($mailBody);
	envoieMailWithSmtp($contact["email_contact"], $mailObject, $mailBody, $ini['fromMail']);
	}
	
	/**
	*	R�cup�re les informations d'un contact
	**/
	function getContactInfo($id) {
		$conn = dbConnect3(); 
		$sql = "SELECT * FROM v_users WHERE uid = $id";
		writelog($sql);
		$res = pg_query($conn, $sql);
		$row = pg_fetch_assoc($res);
		dbClose($conn);
		writelog("Envoie du mail � ".$row["email_contact"]);
		return ($row);
	}
?>