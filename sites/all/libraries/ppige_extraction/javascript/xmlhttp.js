/*********************************************************************************
 Copyright (c) 2002-2005 Armin Burger
 Copyright (c) 2005-2006 GEOSIGNAL - www.geosignal.fr - contact@geosignal.fr


 Permission is hereby granted, free of charge, to any person obtaining
 a copy of this software and associated documentation files (the "Software"),
 to deal in the Software without restriction, including without limitation
 the rights to use, copy, modify, merge, publish, distribute, sublicense,
 and/or sell copies of the Software, and to permit persons to whom the Software
 is furnished to do so, subject to the following conditions:

 The above copyright notice and this permission notice shall be included
 in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHOR OR
 COPYRIGHT HOLDER BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

 --------------------------------------------------------------------------------

 EXAMPLES FOR XMLHTTP TAKEN FROM DREW MCLELLAN ON
 http://www.xml.com/pub/a/2005/02/09/xml-http-request.html
 with additional ideas/concepts from
 Chirp Internet: www.chirp.com.au

**********************************************************************************/
var xn = 0;
var maploading = false;
var withCrossfade = false;

/**
 * GENERIC XMLHTTP FUNCTIONS
 */
function AjaxRequest()
{
    var req;
    var xurl;

    //var method = "GET";
    //var nocache = true;

    this.loadXMLDoc = function(url, async)
    {
        xurl = url;
        async = typeof(async) != "undefined" ? async : true;

        // branch for native XMLHttpRequest object
        if (window.XMLHttpRequest) {
            try {
                req = new XMLHttpRequest();
            } catch(e) {
                req = false;
            }
        // branch for IE/Windows ActiveX version
        } else if (window.ActiveXObject) {
            try {
                req = new ActiveXObject("Msxml2.XMLHTTP");
            } catch(e) {
                try {
                    req = new ActiveXObject("Microsoft.XMLHTTP");
                } catch(e) {
                    req = false;
                }
            }
        }

        if (req) {
            // Asynchrone
            if(async) {
                req.onreadystatechange = processReqChange;
                req.open("GET", url, true);

                // headers to avoid caching
                req.setRequestHeader('If-Modified-Since', 'Sat, 1 Jan 2000 00:00:00 GMT');
                req.setRequestHeader("Cache-Control", "no-cache, must-revalidate, private, pre-check=0, post-check=0, max-age=0");
                req.setRequestHeader("Pragma", "no-cache");

                req.send(null);

            // Synchrone
            } else {
                var responseText;

                req.open("GET", url, false);
                req.send(null);

                responseText = req.responseText;

                if(responseText != "") {
                  // processing statements go here
                  try {
                    eval('response = ' + responseText);
                    method = response.method;

                    eval(method + '(\'\',' + responseText + ')');
              		} catch (e) {
              		  //alert("xmlhttp error !\n\n"+e.name+"\n"+e.message+"\n\n"+"response = " + responseText+(typeof method != "undefined" ? "\n"+method+"('',"+responseText+")" : ""));
              		}
                }
            }
        }
    }

    var processReqChange = function()
    {
        // only if req shows "complete"
        if (req.readyState == 4) {
            // only if "OK"
            if (req.status == 200) {
                var responseText = req.responseText;

                if(responseText != "") {
                  // processing statements go here
                  // alert(req.responseText);
                  try {
                    eval('response = ' + responseText);
                    method = response.method;
  
                    eval(method + '(\'\',' + responseText + ')');
              		} catch (e) {
              		  //alert("xmlhttp error !\n\n"+e.name+"\n"+e.message+"\n\n"+"response = " + responseText+(typeof method != "undefined" ? "\n"+method+"('',"+responseText+")" : ""));
              		}
                }

            } else {
                if (xn < 4) {
                    xn++;
                    //alert(xn);
                    this.loadXMLDoc(xurl);
                    //var reqn = new AjaxRequest();
                    //reqn.loadXMLDoc(xurl);
                } else {
                    //alert("There was a problem retrieving the data:\n" + req.statusText);
                }
            }
        }
    }
}



/*==================================================================================================*/


/**
 * P.MAPPER-RELATED XMLHTTP FUNCTIONS
 */

// For loading/updating the MAP
function updateMap(url, response) {
    //var loadObj = document.getElementById("loading");

    if (response != '') {
        // Response mode
        // Reload application when PHP session expired
        var sessionerror = response.sessionerror;
        if (sessionerror == 'true') {
           errormsg = localeList['sessionExpired'];
           alert(errormsg);
           window.location.reload();
           return false;
        }

        // Test for resize update (for IE)
        if (response.resizeupdate == 'true') {
            //loadObj.style.visibility = "hidden";
            stoploading();
            return false;
        }

        rBxL = response.refBoxStr.split(',');

        //var refW = response.refW;
        //var refH = response.refH;
        minx_geo = parseFloat(response.minx_geo);
        maxy_geo = parseFloat(response.maxy_geo);
        xdelta_geo = parseFloat(response.xdelta_geo);
        ydelta_geo = parseFloat(response.ydelta_geo);
        var geo_scale = response.geo_scale;
        var urlPntStr = response.urlPntStr;

        // Conserve la position initiale de l'image dans mapImgPos
        if(typeof(mapImgPos) == "undefined") {
            var theMapFrame  = document.getElementById("mapFrame");
            var theMapImg    = document.getElementById("mapImg");
            var contourCadre = objBorder(theMapFrame);
            mapImgPos = [getRealPosition(theMapImg, "x") + contourCadre, getRealPosition(theMapImg, "y") + contourCadre];
        }
        // Load new map image
        loadMapImg(response.mapBackgroundURL, response.mapURL);

        // On regarde si la légende doit être rafraichie
        if(response.refreshLegend == 1) { initToc(response.checkedGroups, 1); }

        // Check if TOC has to be updated
        var tocStyle = response.tocStyle;
        var refreshToc = eval(response.refreshToc);
        refreshToc = true;
        if (refreshToc && response.refreshLegend != 1) {
            var tocurl = 'x_toc_update.php?' + SID;
            updateTocScale(tocurl, '');
        }

        // Scale-related activities
        writescale(geo_scale);
        setSlider(geo_scale);
        pMap_setMapScale(geo_scale);

        // Reference image: set DHTML objects
        setRefBox(rBxL[0], rBxL[1], rBxL[2], rBxL[3]);

        // reset cursor
        setCursor(false);

        // Update SELECT tool OPTIONs in case of 'select' mode
        if (document.varform.mode.value == 'nquery') {
            var selurl = 'x_select.php?'+ SID + '&activegroup=' + getSelectLayer() ;
            updateSelectTool(selurl, '');
        }


        //Update map link
        var dg = getLayers();
        var maxx_geo = xdelta_geo + minx_geo;
        var miny_geo = maxy_geo - ydelta_geo;
        var me = minx_geo + ',' + miny_geo + ',' + maxx_geo + ',' + maxy_geo;
        var confpar = config.length > 0 ? '&config=' + config : '';
        var urlPntStrPar = urlPntStr.length > 1 ? '&up=' + urlPntStr : '';
        var loc = window.location;
        var linkhref = loc.protocol + '//' + loc.hostname + loc.port + loc.pathname + '?dg=' + dg + '&me=' + me + '&language=' + gLanguage + confpar + urlPntStrPar;

		    if(document.getElementById('current_maplink') != null) {
          document.getElementById('current_maplink').href = linkhref;
        }

      //JML - Update geographics utils
      if(admin) {
      	if(mc) mc.setExtent(minx_geo, maxy_geo, maxx_geo, miny_geo);
      	//Traite le résultat de la sélection
      	if(response.queryResults) updateListeSelection(response.queryResults);
      }
      
      stoploading();

   } else {
        // Input mode
        //if (maploading == false) {
            maploading = true;
            //loadObj.style.visibility = "visible";
            showloading();
            var req = new AjaxRequest();
            req.loadXMLDoc(url);
        //}
    }
}

// Write the Menu //
function writeTocMenu(tocurl, response) {

    if (response != ''){
      // Response mode
      var numMenu = response.menu;
      ChangeMenuByNum(numMenu);

    } else {
        // Input mode
        var req = new AjaxRequest();
        req.loadXMLDoc(tocurl);
    }
}

// Ecrire ds la DIV Liste //
function chercheTout(tocurl, response) {
    if (response != ''){
        // Response mode
        var tocliste = response.tocliste;
        document.getElementById('liste').innerHTML = tocliste;
        //zoom2scale(document.scaleform.scale.value);
        window.document.body.style.cursor="default";
        stopBloque();
    } else {
        // Input mode
        var req = new AjaxRequest();
        window.document.body.style.cursor="wait";
        showBloque();
        req.loadXMLDoc(tocurl);
    }
}

// Ecrire ds la DIV infos //
function chercheInfo(tocurl, response) {
    if (response != ''){
        // Response mode
        var tocinfo = response.tocinfo;
        document.getElementById('infos').innerHTML = tocinfo;
        //zoom2scale(document.scaleform.scale.value);
        window.document.body.style.cursor="default";
        stopBloque();
    } else {
        // Input mode
        var req = new AjaxRequest();
        window.document.body.style.cursor="wait";
        showBloque();
        req.loadXMLDoc(tocurl);
    }
}

// Ecrire ds la DIV infos //
function chercheInfoBulle(tocurl, response) {
    if (response != ''){
        // Response mode
        var tocinfo = response.tocinfo;
        if (tocinfo != 'VIDE') ouvreOverlibMap(tocinfo);
        //zoom2scale(document.scaleform.scale.value);
        window.document.body.style.cursor="default";

    } else {
        // Input mode
        var req = new AjaxRequest();
        req.loadXMLDoc(tocurl);
    }
}

// Update the TOC //
function updateToc(tocurl, response) {
    if (response != ''){
        // Response mode
        var tocHTML = response.tocHTML;
        //alert(tocHTML);
        if(typeof(response.checkedGroups) != "undefined") {
          legendContainer.checkedGroups = response.checkedGroups.split(",");
        }
        document.getElementById('tree').innerHTML = tocHTML;
        legendContainer.legTxt = tocHTML;

        var tocurl = 'x_toc_update.php?' + SID;
        //window.setTimeout("updateTocScale(tocurl, ''))", 300);
        updateTocScale(tocurl, '');

    } else {
        // Input mode
        var req = new AjaxRequest();
        req.loadXMLDoc(tocurl);
    }
}


// Update the TOC //
function updateTocScale(tocurl, response) {
    // Response mode
    if (response != '') {
        /* UPDATE TOC APPLYING DIFFERENT STYLES TO VISIBLE/NOT-VISIBLE LAYERS*/
        var layers = response.layers;
        for (var l in layers) {
            var spanList = document.getElementsByTagName('span');
            var sl = spanList.length;
            for (var s=0; s<sl; s++) {
                var spanObj = spanList[s];
                if (spanObj.id == 'spxg_' + l) {
                    var checkboxObj = document.getElementById('ginput_' + l);
                    spanObj.className = layers[l];
                    checkboxObj.disabled = (layers[l] != "vis");
                }
            }
        }

    } else {
        // Input mode
        var req = new AjaxRequest();
        req.loadXMLDoc(tocurl);
    }
}



// Show legend over MAP //
function showMapLegend(tocurl, response) {
    if (response != ''){
        // Response mode
        var tocHTML = response.tocHTML;
        //alert(tocHTML);
        var legDiv = document.getElementById('maplegend');
        legDiv.innerHTML = tocHTML;
        legDiv.style.visibility = 'visible';
    } else {
        // Input mode
        var req = new AjaxRequest();
        req.loadXMLDoc(tocurl);
    }
}


// Swap from TOC to LEGEND view //
function swapLegend(tocurl, response) {
    //alert(tocurl);
    if (response != ''){
        // Response mode
        var tocHTML = response.tocHTML;
        var legDiv = document.getElementById('toclegend');
        var tocDiv = document.getElementById('tree');
        tocDiv.innerHTML = tocHTML;
        //legDiv.style.visibility = 'visible';
        //tocDiv.style.visibility = 'hidden';
    } else {
        // Input mode
        var req = new AjaxRequest();
        req.loadXMLDoc(tocurl);
    }
}



// For SELECT tool //
function updateSelectTool(selurl, response) {
    if (response != ''){
        // Response mode
        var selStr = response.selStr;
        document.getElementById('bottomMapFrame').innerHTML = selStr;
    } else {
        // Input mode
        var req = new AjaxRequest();
        req.loadXMLDoc(selurl);
    }
}


function updateSelLayers(mapurl, response) {
    if (response != ''){
        // Response mode
        var sellayers = response.sellayers;

        // Update SELECT tool OPTIONs in case of 'select' mode
        if (document.varform.mode.value == 'nquery') {
            var selurl = 'select.php?'+ SID + '&activegroup=' + getSelectLayer() ;
            updateSelectTool(selurl, '');
        }

    } else {
        // Input mode
        var req = new AjaxRequest();
        req.loadXMLDoc(mapurl);
    }
}

function digitizePoint(digitizeurl, response) {
    if (response != ''){
        // Response mode
        var txt = response.retvalue;
        //alert(txt);
        changeLayersDraw();


    } else {
        // Input mode
        var req = new AjaxRequest();
        req.loadXMLDoc(digitizeurl);
    }
}


function updateCommunes(selurl, response) {
  // Response mode
  if (response != ''){
    if(typeof(response.error) != "undefined") {
      alert(response.error);
      return;
    }

    var select = document.getElementById("listeCommunes");

    select.options.length = 0;
    select.options.add(new Option("Communes du " + response.dept, ""));

    if(response.communes != "") {
      var arrComm = response.communes.split('|');
      for(var i = 0; i < arrComm.length; i++) {
        var arr = arrComm[i].split('#');
        select.options.add(new Option(arr[0], arr[1]));
      }
    }

  // Input mode
  } else {
    var select = document.getElementById("listeCommunes");

    select.options.length = 0;
    select.options.add(new Option("Chargement en cours...", ""));

    var req = new AjaxRequest();
    req.loadXMLDoc(selurl);
  }
}

function goodBye(session_id) {
  var req = new AjaxRequest();
  req.loadXMLDoc("x_clear.php?session_id="+escape(session_id), false);
}
