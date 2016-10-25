/*********************************************************************************
  Copyright (c) 2005-2006 GEOSIGNAL - www.geosignal.fr - contact@geosignal.fr
**********************************************************************************/

popupEPF.prototype = new popup();
popupEPF.prototype.constructor = popupEPF;
popupEPF.superclass = popup.prototype;

function popupEPF(modal, resizable, center, zIndex, x, y, width, height, caption, body) {
  if (arguments.length > 0) { this.init(modal, resizable, center, zIndex, x, y, width, height, caption, body); }
}

popupEPF.prototype.init = function(modal, resizable, center, zIndex, x, y, width, height, caption, body) {
  popupEPF.superclass.init.call(this, modal, resizable, center, zIndex, x, y, width, height, caption, body);
  this.stylePopup("borderColor:'#187585'");
  this.styleCaption("backgroundImage:'url(\"images/popup/vide.png\")', color:'#187585', paddingLeft:'15px'");
  this.styleBody("overflow:'hidden'");
}
