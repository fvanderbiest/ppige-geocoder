/*********************************************************************************
  Copyright (c) 2005-2006 GEOSIGNAL - www.geosignal.fr - contact@geosignal.fr
**********************************************************************************/

// Filtre la liste des extractions
function searchExtractAdmin() {
  setValueParam(arrParamAdmin, "filtre", document.getElementById("filtreExtract").value);
  setValueParam(arrParamAdmin, "date_demande_debut", document.getElementById("dateDebut").value);
  setValueParam(arrParamAdmin, "date_demande_fin", document.getElementById("dateFin").value);
  loadExtractAdmin();
}

function loadExtractionByFilte(){
  var query = '&filtre='+document.getElementById("filtreExtract").value ; 
  query += '&date_demande_debut='+document.getElementById("dateDebut").value ; 
  query += '&date_demande_fin='+document.getElementById("dateFin").value ; 
  sendRequestExtract(query) ;   
}

function sendRequestExtract(query) {
    var url = "admin.php?mode=visualiser_extraction&funcOrderBy=orderByAdmin&funcCallBack=callBackAdmin&admin=1"+query;
    var req = new AjaxRequest();
    req.loadXMLDoc(url, false);    
}

// export csv des extractions
function doExportCsv(){
     alert("dcdcfdcdc");
}

// Affiche la liste des extractions
function loadExtractAdmin() {
    // Response mode
  if(arguments.length != 0) {
    var response = arguments[1];

    if(typeof response.error != "undefined") {
      alert(response.error);
      document.getElementById("divBoard").innerHTML = "";
      return; 
    }
    
    extractAdmin = response.extract;
    document.getElementById("divBoard").innerHTML = response.table.br2nl();
    
  // Input mode
  } else {
    var url = "x_extract2.php?mode=visualiser_extraction&funcResponse=loadExtractAdmin&funcOrderBy=orderByAdmin&funcCallBack=callBackAdmin&admin=1"+paramToUrl(arrParamAdmin)+"&uid="+uid;
    var req = new AjaxRequest();

    req.loadXMLDoc(url, false);    
  }
}

// Gére le tri
function orderByAdmin(arrParam, orderBy) {
  if(getValueParam(arrParam, "order_by") != orderBy) {
    setValueParam(arrParam, "order_by", orderBy);
    setValueParam(arrParam, "direction", "DESC");    
  } else {
    setValueParam(arrParam, "direction", getValueParam(arrParam, "direction") == "ASC" ? "DESC" : "ASC");
  }
  arrParamAdmin = arrParam;
  loadExtractAdmin();
}

// Fonction appelée après une action sur les extractions
function callBackAdmin(response) {
  loadExtractAdmin();
}

function paramToUrl(arrParam) {
  var url = "";
  for(var i=0; i<arrParam.length; i++) {
    url += "&"+arrParam[i][0]+"="+escape(arrParam[i][1]);
  }
  return url;
}

function getIndexParam(arrParam, key) {
  for(var i=0; i<arrParam.length; i++) {
    if(arrParam[i][0] == key) { return i;}
  }
  return -1;
}

function getValueParam(arrParam, key) {
  var index = getIndexParam(arrParam, key);
  if(index != -1) { return arrParam[index][1]; }
}

function setValueParam(arrParam, key, value) {
  var index = getIndexParam(arrParam, key);
  if(index != -1) {
    arrParam[index][1] = value;    
  } else {
    arrParam.push([key, value]);
  } 
}

// Demande de DVD (TRUE/FALSE)
function altDvd(id, obj, oldValue, admin) {
  var value = obj.value;
  
  if(value == "TRUE") {
    if(!confirm("Confirmez-vous la demande de DVD pour l'extraction n°"+id+" ?")) { 
      obj.value = (typeof obj.oldValue != "undefined" ? obj.oldValue : oldValue);
      return; 
    }
  }

  obj.oldValue = value;

  if(admin) {
    document.getElementById("pmeDVD"+id).style.display = (value == "TRUE" ? "inline" : "none");
    
    document.getElementById("pmeDVD2"+id).style.display = "none";
    document.getElementById("pmeDVD3"+id).style.display = "none";
  
    if(value != "TRUE") {
      document.getElementById("pmeDemDVDValid"+id).value = "";
      document.getElementById("pmeDemDVDEnv"+id).value = "FALSE";
      document.getElementById("pmeDemDVDDate"+id).value = "";
      document.getElementById("pmeDemDVDSuivi"+id).value = "";
    }
  }
  
  updateDemandeDvd(id, "demande_dvd", obj);
}

// Demande de DVD acceptée (NULL,TRUE/FALSE)
function altDvd2(id, obj) {
  var value = obj.value;
  
  document.getElementById("pmeDVD2"+id).style.display = (value == "TRUE" ? "inline" : "none");
  document.getElementById("pmeDVD3"+id).style.display = "none";

  if(value != "TRUE") {
    document.getElementById("pmeDemDVDEnv"+id).value = "FALSE";
    document.getElementById("pmeDemDVDDate"+id).value = "";
    document.getElementById("pmeDemDVDSuivi"+id).value = "";
  }

  updateDemandeDvd(id, "demande_dvd_validee", obj);
}

// DVD envoyé (TRUE/FALSE)
function altDvd3(id, obj) {
  var value = obj.value;
  
  document.getElementById("pmeDVD3"+id).style.display = (value == "TRUE" ? "inline" : "none");

  if(value != "TRUE") {
    document.getElementById("pmeDemDVDDate"+id).value = "";
    document.getElementById("pmeDemDVDSuivi"+id).value = "";
  }
  
  updateDemandeDvd(id, "dvd_envoye", obj);
}

function updateDemandeDvd(id, champ, obj) {
  var value = obj.value;
  var url = "x_extract2.php?mode=demande_dvd&id="+id+"&champ="+escape(champ)+"&value="+escape(value);
  var req = new AjaxRequest();
  
  req.loadXMLDoc(url, false);
}

function updateTaille(id, champ, obj) {
  var value = obj.value;
  var url = "x_extract2.php?mode=update&id="+id+"&champ="+escape(champ)+"&value="+escape(value);
  var req = new AjaxRequest();
  
  req.loadXMLDoc(url, false);
}

// Gestion de l'affichage/masquage des détails d'une extraction
function altExt(id) {
  var aExt = document.getElementById("aExt"+id);
  var divExt = document.getElementById("divExt"+id);
  
  if(divExt.style.display == "none") {
    aExt.innerHTML = "-";
    divExt.style.display = "block";
  } else {
    aExt.innerHTML = "+";
    divExt.style.display = "none";
  }
}

// Gére une action liée à une extraction
function actExt(id, action, func) {
  switch (action) {
    case "annuler_extraction":
      if (!confirm("Confirmez-vous l'annulation de l'extraction n°"+id+" ?")) { return; }
      break;
    case "valider_extraction":
      if (!confirm("Confirmez-vous la validation de l'extraction n°"+id+" ?")) { return; }
      break;  
    case "dupliquer_extraction":
      if (!confirm("Confirmez-vous la duplication de l'extraction n°"+id+" ?")) { return; }
      break;
    case "supprimer_fichier_extraction":
      if (!confirm("Confirmez-vous la suppression des fichiers de l'extraction n°"+id+" ?")) { return; }
      break;
    case "terminer_extraction":
      if (!confirm("Etes-vous sûr de vouloir terminer l'extraction n°"+id+" ?")) { return; }
      break;
    case "update_extract": alert("Update ?"); return;
      if (!confirm("Etes-vous sûr de vouloir enregistrer la modification de l'extraction n°"+id+" ?")) { return; }
      break;
  }

  var url = "x_extract2.php?mode="+action+"&id="+id+"&func="+func+"&uid="+uid;
  var req = new AjaxRequest();
  
  req.loadXMLDoc(url, false);  
}

// Retour d'une action liée à une extraction
function actExtReturn() {
  if(typeof arguments[1] == "undefined") { return; }

  var response = arguments[1];
  
  if(typeof response.error != "undefined") {
    alert(response.error);
    return; 
  }
  
  if(typeof response.func != "undefined") { response.func(); }
}

function setParamExtract(name, value) {
  if(name== "HEURE_DEBUT") { alert("La modification de l'heure de départ des extractions n'est pas automatique.\nVous devez modifier la crontab à la main."); }

  var url = "x_extract2.php?mode=modifier_param&name="+escape(name)+"&value="+escape(value);
  var req = new AjaxRequest();

  req.loadXMLDoc(url, false);    
}

function setParamExtractReturn() {
  var response = arguments[1];

  if(typeof response.error != "undefined") { alert(response.error); }
}

function exporterCSV() {
  // Response mode
  if(arguments.length != 0) {
    var response = arguments[1];

    if(typeof response.error != "undefined") {
      alert(response.error);
      return; 
    }

    showPopupDialog("Export CSV", "<a title='Télecharger le fichier csv' href='"+response.fichier+"'><b>Télecharger</b></a>", 350, 120, function(){});
    
  // Input mode
  } else {
    var url = "x_extract2.php?mode=exporter_csv&filtre="+escape(document.getElementById("filtreExtract").value)+"&date_demande_debut="+escape(document.getElementById("dateDebut").value)+"&date_demande_fin="+escape(document.getElementById("dateFin").value);
    var req = new AjaxRequest();

    req.loadXMLDoc(url, false);    
  }
}

function openExtractWindow(pgUrl,title,w,h) {
  var left = (screen.width/2)-(w/2);
  var top = (screen.height/2)-(h/2);
  var editionExtractWin = window.open (pgUrl, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=no, copyhistory=no, width='+w+', height='+h+', top='+top+', left='+left);
}

function updateExtract(id) {
  var arrParamUpd = new Array();
  setValueParam(arrParamUpd, "date_demande", document.getElementById("date_demande").value);
  setValueParam(arrParamUpd, "date_debut_traitement", document.getElementById("date_debut_traitement").value);
  setValueParam(arrParamUpd, "date_fin_traitement", document.getElementById("date_fin_traitement").value);
  setValueParam(arrParamUpd, "date_validation", document.getElementById("date_validation").value);
  setValueParam(arrParamUpd, "date_annulation", document.getElementById("date_annulation").value);
  setValueParam(arrParamUpd, "date_suppression", document.getElementById("date_suppression").value);
  //setValueParam(arrParamUpd, "titre_carte", document.getElementById("titre_carte").value);
  setValueParam(arrParamUpd, "commune", document.getElementById("commune").value);
  //setValueParam(arrParamUpd, "extent_carte", document.getElementById("extent_carte").value);
  //setValueParam(arrParamUpd, "couche_vecteur", document.getElementById("couche_vecteur").value);
  //setValueParam(arrParamUpd, "couche_raster", document.getElementById("couche_raster").value);
  setValueParam(arrParamUpd, "format_vecteur", document.getElementById("format_vecteur").value);
  setValueParam(arrParamUpd, "format_raster", document.getElementById("format_raster").value);
  setValueParam(arrParamUpd, "systeme_projection", document.getElementById("systeme_projection").value);
  setValueParam(arrParamUpd, "attente_vecteur", document.getElementById("attente_vecteur").checked);
  setValueParam(arrParamUpd, "attente_raster", document.getElementById("attente_raster").checked);
  setValueParam(arrParamUpd, "fichier_zip", document.getElementById("fichier_zip").value);
  //setValueParam(arrParamUpd, "fichier_supprime", document.getElementById("fichier_supprime").checked);
  setValueParam(arrParamUpd, "taille_fichier", document.getElementById("taille_fichier").value);
  setValueParam(arrParamUpd, "demande_dvd", document.getElementById("demande_dvd").checked);
  setValueParam(arrParamUpd, "demande_dvd_validee", document.getElementById("demande_dvd_validee").checked);
  setValueParam(arrParamUpd, "dvd_envoye", document.getElementById("dvd_envoye").checked);
  setValueParam(arrParamUpd, "date_dvd_envoye", document.getElementById("date_dvd_envoye").value);
  setValueParam(arrParamUpd, "num_courrier_suivi", document.getElementById("num_courrier_suivi").value);
  setValueParam(arrParamUpd, "fichier_licence", document.getElementById("fichier_licence").value);
  //setValueParam(arrParamUpd, "remote_addr", document.getElementById("remote_addr").value);
  setValueParam(arrParamUpd, "dossier_contact", document.getElementById("dossier_contact").value);
  setValueParam(arrParamUpd, "etat_demande", document.getElementById("etatDemandeField").value);
  setValueParam(arrParamUpd, "zone_geograpique", document.getElementById("zoneGeoField").value);
  //setValueParam(arrParamUpd, "extent_extr", document.getElementById("extent_extr").value);
  
  
  setValueParam(arrParamUpd, "id_format_vecteur", document.getElementById("id_format_vecteur").value);
  setValueParam(arrParamUpd, "id_format_raster", document.getElementById("id_format_raster").value);
  setValueParam(arrParamUpd, "status_vecteur", document.getElementById("status_vecteur").value);
  setValueParam(arrParamUpd, "status_raster", document.getElementById("status_raster").value);
  setValueParam(arrParamUpd, "fichier_licence", document.getElementById("fichier_licence").value);
  //setValueParam(arrParamUpd, "fichier_map", document.getElementById("fichier_map").value);
  setValueParam(arrParamUpd, "remote_addr", document.getElementById("remote_addr").value);
  //setValueParam(arrParamUpd, "process_id", document.getElementById("process_id").value);
  setValueParam(arrParamUpd, "dossier_temp", document.getElementById("dossier_temp").value);
  //setValueParam(arrParamUpd, "process_id_zip", document.getElementById("process_id_zip").value);
  setValueParam(arrParamUpd, "id_projection", document.getElementById("id_projection").value);
  setValueParam(arrParamUpd, "organisme", document.getElementById("organisme").value);
  setValueParam(arrParamUpd, "contact", document.getElementById("contact").value);
  setValueParam(arrParamUpd, "taille_reelle", document.getElementById("taille_reelle").value);
  setValueParam(arrParamUpd, "insee_extraction", document.getElementById("insee_extraction").value);
  
  var url = "x_extract2.php?mode=update_extract&admin=1&id="+id+paramToUrl(arrParamUpd);
  
  var req = new AjaxRequest();

  req.loadXMLDoc(url, false);
}

function onChangeListener(obj) {
  if (obj) {
    var index = obj.selectedIndex;
    var oField = obj.previousSibling;
    if (oField.getAttribute('type') == 'hidden') {
      oField.value = obj.options[index].id;
    }
  }
}


