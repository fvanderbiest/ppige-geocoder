/*********************************************************************************
  Copyright (c) 2005-2006 GEOSIGNAL - www.geosignal.fr - contact@geosignal.fr
**********************************************************************************/

var _popupDialog; 

function showPopupDialog(caption, body, width, height, fctCallBack) {
  if(typeof(fctCallBack) == "undefined") { fctCallBack = function(){} }
  if(typeof(_popupDialog) == "undefined") { _popupDialog = new popupDialog(fctCallBack); }
  var dPDBody = document.getElementById("dPDBody");
  dPDBody.style.overflow = "hidden";
  _popupDialog.show(caption, body, width, height);
}

function showPopupDialogMD(body) {
  var fctCallBack = function(){};
  var caption = "Fiche méta-donnée";
  var width = 700;
  var height = 450;
  if(typeof(_popupDialog) == "undefined") { _popupDialog = new popupDialog(fctCallBack); }
  var dPDBody = document.getElementById("dPDBody");
  dPDBody.style.overflow = "auto";
  _popupDialog.show(caption, body, width, height);
}

//******************************************************************************

popupDialog.prototype = new popupEPF();
popupDialog.prototype.constructor = popupDialog;
popupDialog.superclass = popupEPF.prototype;

// Constructeur
function popupDialog(fctCallBack) {
	if (arguments.length > 0) { this.init(fctCallBack); }
}

// Initialise la fiche
popupDialog.prototype.init = function(fctCallBack) {
  var self = this; 
  var body;
  
  body  = "<table cellpadding=10 width='100%'>";
  body +=   "<tr>";
  body +=     "<td>";
  body +=       "<div id='dPDBody' style='overflow:hidden;'></div>";
  body +=     "</td>";
  body +=   "</tr>";
  body +=   "<tr>";
  body +=     "<td>";
  body +=       "<form>";
  body +=       "<center><a href='#' id='aPDValider'></a></center>";
  body +=       "</form>";
  body +=     "</td>";
  body +=   "</tr>";
  body += "</table>";    

  popupDialog.superclass.init.call(this, true, false, 1, 1000, 0, 0, 300, 250, "", body);

  this._fctCallBack = fctCallBack;
  this._dPDBody = document.getElementById("dPDBody");
  document.getElementById("aPDValider").onclick = function(e) { self.hide() };
}

popupDialog.prototype.show = function(caption, body, width, height) {
  if(typeof(width) == "undefined") { width = this.width(); }
  if(typeof(height) == "undefined") { height = this.height(); }
  this.resizeTo(width, height);
  this.caption(caption);
  this._dPDBody.style.height = (this.clientHeight() - 70)+"px"; 
  this._dPDBody.style.visibility = "visible";
  this._dPDBody.innerHTML = body;
  popupDialog.superclass.show.call(this);
}

popupDialog.prototype.onKeypress = function(keyCode) {
  if(keyCode == 27) { this.hide(); } 
}

popupDialog.prototype.hide = function() {
  this._dPDBody.style.visibility = "hidden";
  popupDialog.superclass.hide.call(this);
  this._fctCallBack();
}
