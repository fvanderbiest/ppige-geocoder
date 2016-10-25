/*********************************************************************************
  Copyright (c) 2005-2006 GEOSIGNAL - www.geosignal.fr - contact@geosignal.fr
**********************************************************************************/

// ************************************************************
// Retourne la taille de la fenêtre
// ************************************************************

function winSize(dimension) {
  var winix = document.body.clientWidth;
  var winiy = document.body.clientHeight;
  
  if (winix < 1 || winiy < 1)  {
      winix = parseInt(window.innerWidth);
      winiy = parseInt(window.innerHeight);
  }
  
  if(typeof(dimension) == "undefined") { return [winix, winiy]; }
  if(dimension == "x") { return winix; }
  if(dimension == "y") { return winiy; }  
}

// ************************************************************
// Retourne le type d'opacité d'un objet
// ************************************************************

function objOpacityType(obj) {
  if (typeof obj == "string") { obj = document.getElementById(obj); }
  if(typeof obj.style.opacity != 'undefined') { return 'w3c'; }
  if(typeof obj.style.MozOpacity != 'undefined') { return 'moz'; }
  if(typeof obj.style.KhtmlOpacity != 'undefined') { return 'khtml'; }
  if(typeof obj.filters == 'object') { return (obj.filters.length > 0 && typeof obj.filters.alpha == 'object' && typeof obj.filters.alpha.opacity == 'number') ? 'ie' : 'none'; }
  return 'none';
}

// ************************************************************
// Alias de objOpacityType
// ************************************************************

function getOpacityType(obj) {
  return objOpacityType(obj);
}

// ************************************************************
// Retourne et/ou initialise l'opacité d'un objet
// ************************************************************

function objOpacity(obj, val, type) {
  if (typeof obj == "string") { obj = document.getElementById(obj); }
  if (typeof(type) == "undefined") { type = objOpacityType(obj); }

  switch (type) {
  case 'ie' :
    if (typeof(val) != "undefined") { obj.filters.alpha.opacity = val * 100; }
    return (obj.filters.alpha.opacity / 100);
  
  case 'khtml' :
    if (typeof(val) != "undefined") { obj.style.KhtmlOpacity = val; }
    return obj.style.KhtmlOpacity;
  
  case 'moz' :
    if (typeof(val) != "undefined") { obj.style.MozOpacity = (val == 1 ? 0.9999999 : val); } 
    return obj.style.MozOpacity;
  
  case 'w3c' :
    if (typeof(val) != "undefined") { obj.style.opacity = (val == 1 ? 0.9999999 : val); } 
    return obj.style.opacity;
  }
}

// ************************************************************
// Alias de objOpacity 
// ************************************************************

function setOpacity(obj, val, type) {  
  return objOpacity(obj, val, type);
}

// ************************************************************
// getOpacityValueForPrint 
// ************************************************************

function getOpacityValueForPrint(obj) {	
	if (typeof obj == "string") { obj = document.getElementById(obj); }
	
	switch(getOpacityType(obj)) {
	case 'ie' :
		return obj.filters.alpha.opacity;
		
	case 'khtml' :
		return obj.style.KhtmlOpacity * 100;
		
	case 'moz' : 
		return  obj.style.MozOpacity * 100;
		
	default : 
		return obj.style.opacity * 100;
	}
}

// ************************************************************
// Retourne la taille du bord d'un objet
// ************************************************************

function objBorder(obj) {
  if (typeof obj == "string") { obj = document.getElementById(obj); }
  var border = parseInt(obj.style.borderWidth)
  if (isNaN(border)) { return 0; }
  return border; 
}

// ************************************************************
// Retourne la position réelle d'un objet
// ************************************************************

function getRealPosition() {
	var pos = (arguments[1] == 'x') ? arguments[0].offsetLeft : arguments[0].offsetTop;
  var tmp = arguments[0].offsetParent;
	while(tmp != null) {
		pos += (arguments[1] == 'x') ? tmp.offsetLeft : tmp.offsetTop;
		tmp = tmp.offsetParent;
	}
	
	return pos;
}

// ************************************************************
// Gestion du préloadage d'images
// ************************************************************

var _preloadArr = [];

function preloadImg() {
  var fct   = arguments[arguments.length - 1];
  var isFct = typeof(fct) == "function";
  var len   = isFct ? arguments.length - 1 : arguments.length; 
  var index = _preloadArr.length;
  var cpt   = 0;
  
  for(var i = 0; i < _preloadArr.length; i++) {
    if(_preloadArr[i] == null) {
      index = i;
      break;
    }
  }
  
  _preloadArr[index] = [];
  
  for(var i = 0; i < len; i++) {
    _preloadArr[index][i]        = new Image();
    _preloadArr[index][i].src    = arguments[i];
    _preloadArr[index][i].onload = function() {
      cpt++;
      if(cpt == len) {
        if (isFct) { fct(); }
        _preloadArr[index] = null;
        compactPreloadArr();           
      }
    }
  }
}

function compactPreloadArr() {
  var len = _preloadArr.length;
  
  for(var i = len - 1; i >= 0; i--) {
    if(_preloadArr[i] == null) {
      _preloadArr.length--; 
    } else {
      break;
    }
  }
}

// ************************************************************
// Gestion du déplacement de la map par les fléches cardinales
// ************************************************************

var _moveMapIntervalId = 0;

function moveMap(left, top, fct) {
  var moveMapPixelStep = 40;
  var moveMapTimeStep  = 1;
  
  var mapimgLayer   = document.getElementById('mapimgLayer');
  var mapbgimgLayer = document.getElementById('mapBgLayer');
  var curL          = objLeft(mapimgLayer);;
  var curT          = objTop(mapimgLayer);
  var endL          = curL + left;
  var endT          = curT + top;
  var pixelStepL    = Math.abs(left) > Math.abs(top) ? moveMapPixelStep : Math.abs(left) / Math.abs(top) * moveMapPixelStep;
  var pixelStepT    = Math.abs(top) > Math.abs(left) ? moveMapPixelStep : Math.abs(top) / Math.abs(left) * moveMapPixelStep;
  var intervalFct   = function() {
    var exit = true;

    if(left > 0) {
      curL += pixelStepL;
      if(parseInt(curL) < endL) {
        mapimgLayer.style.left   = parseInt(curL) + "px";
        mapbgimgLayer.style.left = parseInt(curL) + "px";
        exit = false; 
      } else {
        mapimgLayer.style.left   = endL + "px";
        mapbgimgLayer.style.left = endL + "px";
      }
    } else {
      curL -= pixelStepL;
      if(parseInt(curL) > endL) {
        mapimgLayer.style.left   = parseInt(curL) + "px";
        mapbgimgLayer.style.left = parseInt(curL) + "px";
        exit = false; 
      } else {
        mapimgLayer.style.left   = endL + "px";
        mapbgimgLayer.style.left = endL + "px";
      }
    }

    if(top > 0) {
      curT += pixelStepT;
      if(parseInt(curT) < endT) {
        mapimgLayer.style.top   = parseInt(curT) + "px";
        mapbgimgLayer.style.top = parseInt(curT) + "px";
        exit = false;
      } else {
        mapimgLayer.style.top   = endT + "px";
        mapbgimgLayer.style.top = endT + "px";
      }
    } else {
      curT -= pixelStepT;
      if(parseInt(curT) > endT) {
        mapimgLayer.style.top   = parseInt(curT) + "px";
        mapbgimgLayer.style.top = parseInt(curT) + "px";
        exit = false; 
      } else {
        mapimgLayer.style.top   = endT + "px";
        mapbgimgLayer.style.top = endT + "px";
      }
    }

		var clipT = 0;
		var clipR = mapW;
		var clipB = mapH;
		var clipL = 0;

		if (objTop(mapimgLayer) > 0) {
			clipB = mapH - objTop(mapimgLayer);	
		} else {
			clipT = -1 * objTop(mapimgLayer);     
		}
		
		if (objLeft(mapimgLayer) > 0) {
			clipR = mapW - objLeft(mapimgLayer);
		} else {
			clipL = -1 * objLeft(mapimgLayer);
		}       

		mapimgLayer.style.clip   = 'rect(' + clipT + 'px ' + clipR + 'px ' + clipB + 'px ' + clipL + 'px)';
		mapbgimgLayer.style.clip = 'rect(' + clipT + 'px ' + clipR + 'px ' + clipB + 'px ' + clipL + 'px)';

    if(exit == true) {
      clearInterval(_moveMapIntervalId);
      _moveMapIntervalId = 0;
      fct();
    }  
  }

  resetMeasure();

  if(_moveMapIntervalId == 0) { _moveMapIntervalId = setInterval(intervalFct, moveMapTimeStep); }
}

// ************************************************************
// Objet String
// ************************************************************

String.prototype.trim = function(ch) {
	var x = this;

	x = x.trimL(ch);
	x = x.trimR(ch);

	return x;
}

String.prototype.trimL = function(ch) {
	var x = this;

	// Caractère d'echappement
	if(ch == undefined) {
		x = x.replace(/^\s*(.*)/, "$1");
		return x;
	}

	// Autre caractère
	while(x.charAt(0) == ch) {
		x = x.substr(1);
	}
	return x;
}

String.prototype.trimR = function(ch) {
	var x = this;

	// Caractère d'echappement
	if(ch == undefined) {
		x = x.replace(/(.*?)\s*$/, "$1");
		return x;
	}

	// Autre caractère
	while (x.charAt(x.length - 1) == ch) {
		x = x.substr(0, x.length - 1);
	}
	return x;
}

String.prototype.br2nl = function () {
	return this.replace (/\<br ?\/?\>/g, "\n");
}

// ************************************************************
// Crée un élément
// ************************************************************

function createElement(element) {
  return (typeof(document.createElementNS) != "undefined" ? document.createElementNS("http://www.w3.org/1999/xhtml", element.toLowerCase()) : document.createElement(element.toLowerCase()));
}

// Prints human-readable information about a variable
function print_r(object){
   var maxIterations = 1000;
   // Max depth that Dumper will traverse in object
   var maxDepth = -1;
   var iterations = 0;
   var indent = 1;
   var indentText = " ";
   var nl = "\n";
   // Holds properties of top-level object to traverse - others are ignored
   var properties = null;

   function pad(len){
       var s = "";
       for (var i=0; i<len; i++){
           s += indentText;
       }
       return s;
   }

   function inspect(o){
       var level = 1;
       var indentLevel = indent;
       var r = "";
       if (arguments.length>1 && typeof(arguments[1])=="number"){
           level = arguments[1];
           indentLevel = arguments[2];
           if (o == object){
               return "[original object]";
           }
       } else {
           iterations = 0;
           object = o;
           // If a list of properties are passed in
           if (arguments.length>1){
               var list = arguments;
               var listIndex = 1;
               if (typeof(arguments[1])=="object"){
                   list = arguments[1];
                   listIndex = 0;
               }
               for (var i=listIndex; i<list.length; i++){
                 if (properties == null){ properties = {}; }
                   properties[list[i]]=1;
               }
           }
       }
       // Just in case, so the script doesn't hang
       if (iterations++>maxIterations){ return r +"\n[Max Iterations Reached]"; }

       if (maxDepth != -1 && level > (maxDepth+1)){
           return "...";
       }
       // undefined
       if (typeof(o)=="undefined"){
           return "[undefined]";
       }
       // NULL
       if (o==null){
           return "[null]";
       }
       // DOM Object
       if (o==window){
           return "[window]";
       }
       if (o==window.document){
           return "[document]";
       }
       // FUNCTION
       if (typeof(o)=="function"){
           return "[function]";
       }
       // BOOLEAN
       if (typeof(o)=="boolean"){
           return (o)?"true":"false";
       }
       // STRING
       if (typeof(o)=="string"){
           return "'" + o + "'";
       }
       // NUMBER
       if (typeof(o)=="number"){
           return o;
       }
       if (typeof(o)=="object"){
           if (typeof(o.length)=="number" ){
               // ARRAY
               if (maxDepth != -1 && level > maxDepth){
                   return "[ ... ]";
               }
               r = "[";
               for (var i=0; i<o.length;i++){
                   if (i>0){
                       r += "," + nl + pad(indentLevel);
                   } else {
                       r += nl + pad(indentLevel);
                   }
                   r += inspect(o[i],level+1,indentLevel-0+indent);
               }
               if (i > 0){
                   r += nl + pad(indentLevel-indent);
               }
               r += "]";
               return r;
           } else {
               // OBJECT
               if (maxDepth != -1 && level > maxDepth){
                   return "{ ... }";
               }
               r = "{";
               var count = 0;
               for (i in o){
                   if (o==object && properties!=null && properties[i]!=1){
                       // do nothing with this node
                   } else {
                       try {
                           if (typeof(o[i]) != "unknown"){
                               var processAttribute = true;
                               // Check if this is a DOM object, and if so, if we have to limit properties to look at
                               if ( o.ownerDocument || o.tagName || (o.nodeType && o.nodeName)){
                                   processAttribute = false;
                                   if (i=="tagName" || i=="nodeName" || i=="nodeType" || i=="id" || i=="className"){
                                       processAttribute = true;
                                   }
                               }
                               if (processAttribute){
                                   if (count++>0){
                                       r += "," + nl + pad(indentLevel);
                                   } else {
                                       r += nl + pad(indentLevel);
                                   }
                                   r += "'" + i + "' : " + inspect(o[i],level+1,indentLevel-0+i.length+6+indent);
                               } else {
                                   //r += "'" + i + "' : " + typeof(o[i]);
                               }
                           }
                       } catch(e) {
                           //alert( print_r(e) )
                       }
                   }
               }
               if (count > 0){
                   r += nl + pad(indentLevel-indent);
               }
               r += "}";
               return r;
           }
       }
   }
   return inspect(object);
}


// Dumps information about a variable
function var_dump(v){
  alert(print_r(v));
}

// Finds whether a variable is an array
function is_array(v){
  return (v instanceof Array);
}

// Return TRUE if a value exists in an array
function in_array(needle, a)  {
  return array_search(needle, a) !== false;
}

function array_search(needle, a)  {
  for (var i = 0; i < a.length; i++){
    if (a[i] == needle){
      return i;
    }              
  }
  return false;
}
 
// Disabled
function disableElement(el, bool) {
  try {
    el.disabled = bool;
  }
  catch(E){}
  
  if (el.childNodes && el.childNodes.length > 0) {
    for (var x = 0; x < el.childNodes.length; x++) {
      disableElement(el.childNodes[x], bool);
    }
  }
}

function br2nl(str) {
  return str.br2nl();
}
