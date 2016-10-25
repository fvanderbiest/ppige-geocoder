/****************************************************************************************
  Copyright (c) 2005-2006 GEOSIGNAL - www.geosignal.fr - contact@geosignal.fr (J.Duclos)
****************************************************************************************/

// *****************************************************************************
// Objet popup
// *****************************************************************************
// center : 0 = non
//              1 = oui a chaque affichage du popup
//              2 = oui que pour le 1er affichage du popup
// *****************************************************************************
function popup(modal, resizable, center, zIndex, x, y, width, height, caption, body) {
  if (arguments.length > 0) { this.init(modal, resizable, center, zIndex, x, y, width, height, caption, body); }
}

// *****************************************************************************
// init
// *****************************************************************************
popup.prototype.init = function(modal, resizable, center, zIndex, x, y, width, height, caption, body) {
  var self  = this;
  var oBody = this.getBody();

  this._border          = 2;
  this._headerHeight    = 24;
  this._imgCtrlWidth    = 23;
  this._imgResizeWidth  = 7;
  this._imgResizeHeight = 7;
  this._resizeBarHeight = 9;

  this._modal     = modal;
  this._resizable = resizable;
  this._center    = center;
  this._zIndex    = zIndex;
  this._x         = x;
  this._y         = y;
  this._width     = width;
  this._height    = height;
  this._caption   = caption;
  this._body      = body;

  this._isIE = (document.all && !navigator.userAgent.match(/Opera|Konqueror|Safari/i) ? true : false);

  this._popupWidth  = this._width;
  this._popupHeight = this._height;

  if(!this._isIE) {
    this._popupWidth  -= this._border * 2;
    this._popupHeight -= this._border * 2;
  }

  this._appearance     = 1;
  this._show           = false;
  this._firstShow      = true;
  this._action         = "";
  this._stylePopup     = {};
  this._styleCaption   = {};
  this._styleBody      = {};
  this._styleBottomBar = {};

  // ***************************************************************************

  this._oModal = oBody.appendChild(this.createElement("div"));
  this._oModal.style.visibility = "hidden";
  this._oModal.style.position   = "absolute";
  this._oModal.style.left       = "0px";
  this._oModal.style.top        = "0px";
  this._oModal.style.width      = "100%";
  this._oModal.style.height     = "100%";
  this._oModal.style.zIndex     = this._zIndex;
  this._oModal.style.cursor     = "default";
  this._oModal.innerHTML        = "<span style='width:100%; height:100%;'></span>";

  //****************************************************************************

  if(this._isIE) {
    this._oIFrame = oBody.appendChild(this.createElement("div"));
    this._oIFrame.style.visibility = "hidden";
    this._oIFrame.style.position   = "absolute";
    this._oIFrame.style.left       = this._x + "px";
    this._oIFrame.style.top        = this._y + "px";
    this._oIFrame.style.width      = this._popupWidth + "px";
    this._oIFrame.style.height     = this._popupHeight + "px";
    this._oIFrame.style.zIndex     = this._zIndex;
    this._oIFrame.style.overflow   = "hidden";
    this._oIFrame.innerHTML        = '<IFRAME FRAMEBORDER=0 WIDTH="100%" HEIGHT="100%" SRC=""></IFRAME>';
  }

  //****************************************************************************

  this._oPopup = oBody.appendChild(this.createElement("div"));
  this._oPopup.style.visibility = "hidden";
  this._oPopup.style.position   = "absolute";
  this._oPopup.style.border     = this._border + "px solid black";
  this._oPopup.style.left       = this._x + "px";
  this._oPopup.style.top        = this._y + "px";
  this._oPopup.style.width      = this._popupWidth + "px";
  this._oPopup.style.height     = this._popupHeight + "px";
  this._oPopup.style.zIndex     = this._zIndex;
  this._oPopup.style.overflow   = "hidden";

  // ***************************************************************************

  this._oHeader = this._oPopup.appendChild(this.createElement("div"));
  this._oHeader.style.position        = "absolute";
  this._oHeader.style.backgroundColor = "#FFFFFF";
  this._oHeader.style.left            = "0px";
  this._oHeader.style.top             = "0px";
  this._oHeader.style.width           = this.objClientWidth(this._oPopup) + "px";
  this._oHeader.style.height          = this._headerHeight + "px";

  // ***************************************************************************

  this._oCaption = this._oHeader.appendChild(this.createElement("div"));
  this._oCaption.style.position        = "absolute";
  this._oCaption.style.left            = "0px";
  this._oCaption.style.top             = "0px";
  this._oCaption.style.width           = (this.objClientWidth(this._oHeader) - (this._imgCtrlWidth * 2)) + "px";
  this._oCaption.style.height          = this.objClientHeight(this._oHeader) + "px";
  this._oCaption.style.font            = "bolder 12px Arial";
  this._oCaption.style.paddingTop      = "5px";
  this._oCaption.style.paddingLeft     = "3px";
  this._oCaption.style.overflow        = "hidden";
  this._oCaption.noWrap                = true;

  // ***************************************************************************

  this._oTitleBar = this._oHeader.appendChild(this.createElement("div"));
  this._oTitleBar.style.position = "absolute";
  this._oTitleBar.style.left     = "0px";
  this._oTitleBar.style.top      = "0px";
  this._oTitleBar.style.width    = (this.objClientWidth(this._oHeader) - (this._imgCtrlWidth * 2)) + "px";
  this._oTitleBar.style.height   = this.objClientHeight(this._oHeader) + "px";
  this._oTitleBar.innerHTML      = "<span style='cursor:default; width:100%; height:100%;'></span>";
  this._oTitleBar.style.cursor   = "move";

  var titleBarDblclickHandler = function(e) {
    if(self._appearance == 1) {
      self.reduce();
    } else {
      self.increase();
    }
  }

  var titleBarDblMouseDown = function(e) {
    var m = new mouse(e);

    if (m.button == 1 && self._action == "") {
      var oBody = self.getBody();

      self._action = "move";

      self._refCursorBody = oBody.style.cursor;
      oBody.style.cursor  = self._oTitleBar.style.cursor;
      
      self._oModal.style.visibility = "visible";
      self._oModal.style.zIndex     = self._zIndex + 1;

      self._shiftL = self.objLeft(self._oPopup) - m.x;
      self._shiftT = self.objTop(self._oPopup) - m.y;

      var mouseMoveHandler = function(e) {
        var m = new mouse(e);

        self.moveTo(m.x + self._shiftL, m.y + self._shiftT);
      }

      var mouseUpHandler = function(e) {
        self._action = "";

        if(!self._modal) { self._oModal.style.visibility = "hidden"; }
        self._oModal.style.zIndex = self._zIndex;

        oBody.style.cursor = self._refCursorBody;

        self.removeEvent(document, "mousemove", mouseMoveHandler);
        self.removeEvent(document, "mouseup", mouseUpHandler);
      }

      self.addEvent(document, "mousemove", mouseMoveHandler);
      self.addEvent(document, "mouseup", mouseUpHandler);
    }
  }

  this.addEvent(this._oTitleBar, "dblclick", titleBarDblclickHandler);
  this.addEvent(this._oTitleBar, "mousedown", titleBarDblMouseDown);

  // ***************************************************************************

  this._oResizeBar = this._oPopup.appendChild(this.createElement("div"));
  this._oResizeBar.style.position        = "absolute";
  this._oResizeBar.style.backgroundColor = "#ECE9D8";
  this._oResizeBar.style.left            = "0px";
  this._oResizeBar.style.top             = (this.objClientHeight(this._oPopup) - this._resizeBarHeight) + "px";
  this._oResizeBar.style.width           = this.objClientWidth(this._oPopup) + "px";
  this._oResizeBar.style.height          = this._resizeBarHeight + "px";
  this._oResizeBar.style.overflow        = "hidden";
  this._oResizeBar.innerHTML             = "<div style='position:absolute; bottom:0px; right:0px;'><img src='images/popup/resize.gif'></div>";

  this._oStatus = this._oResizeBar.appendChild(this.createElement("div"));
  this._oStatus.style.position = "absolute";
  this._oStatus.style.left     = "0px";
  this._oStatus.style.top      = "0px";
  this._oStatus.style.width    = (this.objClientWidth(this._oPopup) - this._imgResizeWidth) + "px";
  this._oStatus.style.height   = this._resizeBarHeight + "px";
  this._oStatus.style.overflow = "hidden";

  this._oFermer = this._oStatus.appendChild(this.createElement("div"));
  this._oFermer.style.position   = "absolute";
  this._oFermer.style.visibility = "hidden";
  this._oFermer.style.top        = "0px";
  this._oFermer.style.right      = "0";
  this._oFermer.style.bottom     = "0";
  this._oFermer.style.overflow   = "hidden";
  this._oFermer.style.cursor     = "hand";
  this._oFermer.style.font       = "8px Verdana";
  this._oFermer.color            = "#000000"
  this._oFermer.innerHTML        = "<table cellpadding=0 cellspacing=0><tr><td style='font:8px Verdana;'><a href ='#'>FERMER&nbsp;&nbsp;</td><td width=7></td></tr></table></a>";

  this._oFermer.onclick = function(e) {
    self.hide();
  }

  // ***************************************************************************

  this._oResize = this._oResizeBar.appendChild(this.createElement("div"));
  this._oResize.style.position = "absolute";
  this._oResize.style.left     = (this.objClientWidth(this._oResizeBar) - this._imgResizeWidth) + "px";
  this._oResize.style.top      = (this.objClientHeight(this._oResizeBar) - this._imgResizeHeight) + "px";
  this._oResize.style.width    = this._imgResizeWidth + "px";
  this._oResize.style.height   = this._imgResizeHeight + "px";
  this._oResize.style.cursor   = "se-resize";
  this._oResize.innerHTML      = "<span style='width:100%; height:100%;'></span>";

  this._oResize.onmousedown = function(e) {
    var m = new mouse(e);

    if (m.button == 1 && self._action == "") {
      var oBody = self.getBody();

      self._action = "resize";

      self._refCursorBody  = oBody.style.cursor;
      self._refCursorModal = self._oModal.style.cursor;
      self._refOnMouseUp   = document.onmouseup;
      self._refOnMouseMove = document.onmousemove;

      oBody.style.cursor = self._oResize.style.cursor;

      self._oModal.style.cursor     = self._oResize.style.cursor;
      self._oModal.style.zIndex     = self._zIndex + 1;
      self._oModal.style.visibility = "visible";

      self._eX     = m.x;
      self._eY     = m.y;
      self._shiftL = self.objLeft(self._oPopup) - m.x;
      self._shiftT = self.objTop(self._oPopup) - m.y;

      var mouseMoveHandler = function(e) {
        var m = new mouse(e);
        var width  = m.x < self._eX ? self.objWidth(self._oPopup) - (self._eX - m.x) : self.objWidth(self._oPopup) + (m.x - self._eX);
        var height = m.y < self._eY ? self.objHeight(self._oPopup) - (self._eY - m.y) : self.objHeight(self._oPopup) + (m.y - self._eY);

        if(width < 100) { width = 100; }
        if(height < 80) { height = 80; }

        self.resizeTo(width, height);

        self._eX = m.x;
        self._eY = m.y;
      }

      var mouseUpHandler = function(e) {
        self._action = "";

        if(!self._modal) { self._oModal.style.visibility = "hidden"; }
        self._oModal.style.zIndex = self._zIndex;
        self._oModal.style.cursor = self._refCursorModal;

        oBody.style.cursor = self._refCursorBody;

        self.removeEvent(document, "mousemove", mouseMoveHandler);
        self.removeEvent(document, "mouseup", mouseUpHandler);
      }

      self.addEvent(document, "mousemove", mouseMoveHandler);
      self.addEvent(document, "mouseup", mouseUpHandler);
    }
  }

  // ***************************************************************************

  this._oControl = this._oHeader.appendChild(this.createElement("div"));
  this._oControl.style.position = "absolute";
  this._oControl.style.left     = this.objWidth(this._oTitleBar) + "px";
  this._oControl.style.top      = "0px";
  this._oControl.style.width    = (this.objClientWidth(this._oHeader) - this.objWidth(this._oTitleBar)) + "px";
  this._oControl.style.height   = this.objClientHeight(this._oHeader) + "px";
  this._oControl.style.zIndex   = this._zIndex;

  //****************************************************************************

  this._imgSize = this._oControl.appendChild(this.createElement("img"));
  this._imgSize.src = "images/popup/small.gif";

  var imgSizeClickHandler = function(e) {
    if(self._appearance == 1) {
      self.reduce();
    } else {
      self.increase();
    }
  }

  self.addEvent(this._imgSize, "click", imgSizeClickHandler);

  //****************************************************************************

  this._imgHide = this._oControl.appendChild(this.createElement("img"));
  this._imgHide.src = "images/popup/close.gif";

  var imgHideClickHandler = function(e) { self.hide(); }

  self.addEvent(this._imgHide, "click", imgHideClickHandler);

  //****************************************************************************

  this._oBody = this._oPopup.appendChild(this.createElement("div"));
  this._oBody.style.position        = "absolute";
  this._oBody.style.backgroundColor = "#FFFFFF";
  this._oBody.style.left            = "0px";
  this._oBody.style.top             = this.objHeight(this._oHeader) + "px";
  this._oBody.style.width           = this.objClientWidth(this._oPopup) + "px";
  this._oBody.style.height          = (this.objClientHeight(this._oPopup) - this.objHeight(this._oHeader) - (this._resizable ? this.objHeight(this._oResizeBar) : 0)) + "px";
  this._oBody.style.zIndex          = this._zIndex;
  this._oBody.style.overflow        = "auto";

  //****************************************************************************

  this.caption(this._caption);
  this.body(this._body);

  //****************************************************************************

  var onKeypressHandler = function(e) {
    if(self._show == true) { self.onKeypress(self.keyCode(e)); }
  }

  this.addEvent(document, "keypress", onKeypressHandler);
}

// *****************************************************************************
// onMove
// *****************************************************************************
popup.prototype.onMove = function() {
  //...
}

// *****************************************************************************
// onResize
// *****************************************************************************
popup.prototype.onResize = function() {
  //...
}

// *****************************************************************************
// onKeypress
// *****************************************************************************
popup.prototype.onKeypress = function(keyCode) {
  //...
}

// *****************************************************************************
// moveTo
// *****************************************************************************
popup.prototype.moveTo = function(x, y) {
  this._oPopup.style.left = x + "px";
  this._oPopup.style.top  = y + "px";

  if(typeof(this._oIFrame) != "undefined") {
    this._oIFrame.style.left = x + "px";
    this._oIFrame.style.top  = y + "px";
  }

  this._x = x;
  this._y = y;

  this.onMove();
}

// *****************************************************************************
// resizeTo
// *****************************************************************************
popup.prototype.resizeTo = function(width, height) {
  this._width  = width;
  this._height = height;

  this._popupWidth  = this._width;
  this._popupHeight = this._height;

  if(!this._isIE) {
    this._popupWidth  -= this._border * 2;
    this._popupHeight -= this._border * 2;
  }

  this._oPopup.style.width  = this._popupWidth + "px";
  this._oPopup.style.height = this._popupHeight + "px";

  if(typeof(this._oIFrame) != "undefined") {
    this._oIFrame.style.width  = this._width + "px";
    this._oIFrame.style.height = this._height + "px";
  }

  this._oHeader.style.width = this.objClientWidth(this._oPopup) + "px";

  this._oCaption.style.width = (this.objClientWidth(this._oHeader) - (this._imgCtrlWidth * 2)) + "px";

  this._oTitleBar.style.width = (this.objClientWidth(this._oHeader) - (this._imgCtrlWidth * 2)) + "px";

  this._oControl.style.left  = this.objWidth(this._oTitleBar) + "px";
  this._oControl.style.width = (this.objClientWidth(this._oHeader) - this.objWidth(this._oTitleBar)) + "px";

  this._oBody.style.width  = this.objClientWidth(this._oPopup) + "px";
  this._oBody.style.height = (this.objClientHeight(this._oPopup) - this.objHeight(this._oHeader) - (this._resizable ? this.objHeight(this._oResizeBar) : 0)) + "px";

  this._oResizeBar.style.width = this.objClientWidth(this._oPopup) + "px";
  this._oResizeBar.style.top   = (this.objClientHeight(this._oPopup) - this._resizeBarHeight) + "px";

  this._oStatus.style.width = (this.objClientWidth(this._oPopup) - this._imgResizeWidth) + "px";

  this._oResize.style.left = (this.objClientWidth(this._oResizeBar) - this._imgResizeWidth) + "px";
  this._oResize.style.top  = (this.objClientHeight(this._oResizeBar) - this._imgResizeHeight) + "px";

  this.onResize();
}

// *****************************************************************************
// left
// *****************************************************************************
popup.prototype.left = function(left) {
  if(typeof(left) != "undefined") { this.moveTo(left, this._y); }
  return this._x;
}

// *****************************************************************************
// top
// *****************************************************************************
popup.prototype.top = function(top) {
  if(typeof(top) != "undefined") { this.moveTo(this._x, top); }
  return this._y;
}

// *****************************************************************************
// width
// *****************************************************************************
popup.prototype.width = function(width) {
  if(typeof(width) != "undefined") { this.resizeTo(width, this._height); }
  return this._width;
}

// *****************************************************************************
// height
// *****************************************************************************
popup.prototype.height = function(height) {
  if(typeof(height) != "undefined") { this.resizeTo(this._width, height); }
  return this._height;
}

// *****************************************************************************
// clientWidth
// *****************************************************************************
popup.prototype.clientWidth = function() {
  return this.objWidth(this._oBody);
}

// *****************************************************************************
// clientHeight
// *****************************************************************************
popup.prototype.clientHeight = function() {
  return this.objHeight(this._oBody);
}

// *****************************************************************************
// stylePopup
// *****************************************************************************
popup.prototype.stylePopup = function(style) {
    if (arguments.length > 0) {
      eval("style={"+style+"}");
    if(typeof(style.borderColor) != "undefined") {
      this._stylePopup.borderColor = style.borderColor;
      this._oPopup.style.borderColor = style.borderColor;
    }
  }

    return this._stylePopup;
}

// *****************************************************************************
// caption
// *****************************************************************************
popup.prototype.caption = function(caption) {
    if (arguments.length > 0) {
    this._caption = caption;
    this._oCaption.innerHTML = caption;
  }

    return this._caption;
}

// *****************************************************************************
// styleCaption
// *****************************************************************************
popup.prototype.styleCaption = function(style) {
    if (arguments.length > 0) {
      eval("style={"+style+"}");
    if(typeof(style.backgroundImage) != "undefined") {
      this._styleCaption.backgroundImage = style.backgroundImage;
      this._oHeader.style.backgroundImage = style.backgroundImage;
    }
    if(typeof(style.color) != "undefined") {
      this._styleCaption.color = style.color;
      this._oHeader.style.color = style.color;
    }
    if(typeof(style.paddingLeft) != "undefined") {
      this._styleCaption.paddingLeft = style.paddingLeft;
      this._oCaption.style.paddingLeft = style.paddingLeft;
    }
  }

    return this._styleCaption;
}

// *****************************************************************************
// body
// *****************************************************************************
popup.prototype.body = function(body, iframe, onloadFct) {
  var self = this;

    if (arguments.length > 0) {
      iframe = typeof(iframe) == "undefined" ? false : iframe;
    this._body = body;
    if(iframe == false) {
      this._oBody.innerHTML = body;
      this._oBodyIFrame = this._undefined;
    } else {
      this._oBody.innerHTML = '<IFRAME FRAMEBORDER=0 SCROLLING="'+this.overflowToScrolling(this._oBody.style.overflow)+'" WIDTH="100%" HEIGHT="100%" SRC=""></IFRAME>';
      this._oBodyIFrame = this._oBody.childNodes[0];
      if(typeof(onloadFct) != "undefined") { this.addEvent(this._oBodyIFrame, "load", onloadFct); }
      this._oBodyIFrame.src = body;
    }
  }

    return this._body;
}

// *****************************************************************************
// styleBody
// *****************************************************************************
popup.prototype.styleBody = function(style) {
    if (arguments.length > 0) {
      eval("style={"+style+"}");
    if(typeof(style.overflow) != "undefined") {
      this._styleBody.overflow = style.overflow;
      if(typeof(this._oBodyIFrame) == "undefined") {
        this._oBody.style.overflow = style.overflow;
      } else {
        this._oBodyIFrame.scrolling = this.overflowToScrolling(style.overflow);
      }
    }
  }

    return this._styleBody;
}

// *****************************************************************************
// styleBottomBar
// *****************************************************************************
popup.prototype.styleBottomBar = function(style) {
    if (arguments.length > 0) {
      eval("style={"+style+"}");
    if(typeof(style.fermerVisibility) != "undefined") {
      this._styleBottomBar.fermerVisibility = style.fermerVisibility;
      if(this._show == true) { this._oFermer.style.visibility = style.fermerVisibility; }
    }
  }

    return this._styleBottomBar;
}

// *****************************************************************************
// modal
// *****************************************************************************
popup.prototype.modal = function(modal) {
  if (arguments.length > 0) {
    this._modal = modal;
    this._oModal.style.visibility = modal ? "visible" : "hidden";
  }

  return this._modal;
}

// *****************************************************************************
// center
// *****************************************************************************
popup.prototype.center = function() {
  var left = parseInt((this.objClientWidth(document.body) - this.objWidth(this._oPopup)) / 2);
  var top  = parseInt((this.objClientHeight(document.body) - this.objHeight(this._oPopup)) / 2);

  this.moveTo(left, top);
}

// *****************************************************************************
// show
// *****************************************************************************
popup.prototype.show = function() {
  this.modal(this._modal);
  if(this._appearance == 0) { this.increase(); }
  if (this._center == 1 || (this._center == 2 && this._firstShow == true)) {
    this.center();
    this._firstShow = false;
  }
  this._oPopup.style.visibility = "visible";
  if (this._resizable) {
    this._oResizeBar.style.visibility = "visible";

    if(typeof(this._styleBottomBar.fermerVisibility) != "undefined") {
      this._oFermer.style.visibility = this._styleBottomBar.fermerVisibility;
    }


  }
  if(typeof(this._oIFrame) != "undefined") { this._oIFrame.style.visibility = "visible"; }
  this._show = true;
}

// *****************************************************************************
// hide
// *****************************************************************************
popup.prototype.hide = function() {
  if(typeof(this._oIFrame) != "undefined") { this._oIFrame.style.visibility = "hidden"; }
  this._oModal.style.visibility = "hidden";
  this._oPopup.style.visibility = "hidden";
  this._oResizeBar.style.visibility = "hidden";
  this._oFermer.style.visibility = "hidden";
  this._show = false;
}

// *****************************************************************************
// reduce
// *****************************************************************************
popup.prototype.reduce = function() {
  var height = this.objClientHeight(this._oHeader) + (this._isIE ? this._border * 2 : 0);

  this._imgSize.src = "images/popup/big.gif";
  if(typeof(this._oIFrame) != "undefined") { this._oIFrame.style.height = height + "px"; }
  this._oPopup.style.height = height + "px";
  this._oBody.style.height  = "0px"; // FireFox
  this._appearance = 0;
}

// *****************************************************************************
// increase
// *****************************************************************************
popup.prototype.increase = function() {
  this._imgSize.src = "images/popup/small.gif";
  this._oPopup.style.height = this._popupHeight + "px";
  this._oBody.style.height = (this.objClientHeight(this._oPopup) - this.objHeight(this._oHeader) - (this._resizable ? this.objHeight(this._oResizeBar) : 0)) + "px"; // FireFox
  if(typeof(this._oIFrame) != "undefined") { this._oIFrame.style.height = this._height + "px"; }
  this._appearance = 1;
}

// *****************************************************************************
// overflowToScrolling
// *****************************************************************************
popup.prototype.overflowToScrolling = function(overflow) {
  var scrolling;

  switch(overflow) {
  case "scroll":
    scrolling = "yes";
    break;
  case "hidden":
    scrolling = "no";
    break;
  default:
    scrolling = "auto";
  }

  return scrolling;
}

// *****************************************************************************
// realPosition
// *****************************************************************************
popup.prototype.realPosition = function(obj, xy) {
    var pos = (xy == 'x') ? obj.offsetLeft : obj.offsetTop;
  var tmp = obj.offsetParent;

    while(tmp != null) {
        pos += (xy == 'x') ? tmp.offsetLeft : tmp.offsetTop;
        tmp = tmp.offsetParent;
    }

    return pos;
}

// *****************************************************************************
// objLeft
// *****************************************************************************
popup.prototype.objLeft = function(obj) {
  return parseInt(obj.style.left);
}

// *****************************************************************************
// objTop
// *****************************************************************************
popup.prototype.objTop = function(obj) {
  return parseInt(obj.style.top);
}

// *****************************************************************************
// objWidth
// *****************************************************************************
popup.prototype.objWidth = function(obj) {
  var width  = parseInt(obj.style.width);
  var border = parseInt(obj.style.borderWidth);

  if(!this._isIE && !isNaN(border)) { width += this._border * 2; }

  return width;
}

// *****************************************************************************
// objHeight
// *****************************************************************************
popup.prototype.objHeight = function(obj) {
  var height = parseInt(obj.style.height);
  var border = parseInt(obj.style.borderWidth);

  if(!this._isIE && !isNaN(border)) { height += this._border * 2; }

  return height;
}

// *****************************************************************************
// objClientWidth
// *****************************************************************************
popup.prototype.objClientWidth = function(obj) {
  return parseInt(obj.clientWidth);
}

// *****************************************************************************
// objClientHeight
// *****************************************************************************
popup.prototype.objClientHeight = function(obj) {
  return parseInt(obj.clientHeight);
}

// *****************************************************************************
// getBody
// *****************************************************************************
popup.prototype.getBody = function(doc) {
  if(typeof(doc) == "undefined") { doc = document; }
  return doc.getElementsByTagName('body')[0];
}

// *****************************************************************************
// createElement
// *****************************************************************************
popup.prototype.createElement = function(element, doc) {
  if(typeof(doc) == "undefined") { doc = document; }
  return (typeof doc.createElementNS != "undefined") ? doc.createElementNS("http://www.w3.org/1999/xhtml", element.toLowerCase()) : doc.createElement(element.toLowerCase());
}

// *****************************************************************************
// addEvent
// *****************************************************************************
popup.prototype.addEvent = function(obj, type, callback) {
  if(document.all) {
    obj.attachEvent("on"+type, callback);
  } else {
    obj.addEventListener(type, callback, false);
  }
}

// *****************************************************************************
// removeEvent
// *****************************************************************************
popup.prototype.removeEvent = function(obj, type, callback) {
  if(document.all) {
    obj.detachEvent("on"+type, callback);
  } else {
    obj.removeEventListener(type, callback, false);
  }
}

// *****************************************************************************
// singleId
// *****************************************************************************
popup.prototype.singleId = function(id, doc) {
  if(typeof(doc) == "undefined") { doc = document; }
  if(typeof(this._arrayId) == "undefined") { this._arrayId = []; }
  var realId;
  var cpt = 0;
  do { realId = "POPUP_" + (cpt++) + "_" + id; } while(doc.getElementById(realId) != null);
  this._arrayId[id] = realId;
  return realId;
}

// *****************************************************************************
// getPrivateObjById
// *****************************************************************************
popup.prototype.getPrivateObjById = function(id, doc) {
  if(typeof(doc) == "undefined") { doc = document; }
  if(typeof(this._arrayId) != "undefined") { return doc.getElementById(this._arrayId[id]); }
}

// *****************************************************************************
// keyCode
// *****************************************************************************
popup.prototype.keyCode = function(e) {
    if (document.all) { return event.keyCode; }
    return e.keyCode;
}

// *****************************************************************************
// Objet mouse
// *****************************************************************************
function mouse(e) {
  if (arguments.length > 0) { this.init(e); }
}

// *****************************************************************************
// init
// *****************************************************************************
mouse.prototype.init = function(e) {
  if (document.all) {
    this.button = event.button;
    this.x      = event.clientX;
    this.y      = event.clientY;
  } else {
    this.button = e.which;
    this.x      = e.pageX;
    this.y      = e.pageY;
  }
}
