/* global require, hljs */
/* jshint ignore:start */
/* Flickr Justified layout (https://github.com/flickr/justified-layout) */
require=function a(b,c,d){function e(g,h){if(!c[g]){if(!b[g]){var i="function"==typeof require&&require;if(!h&&i)return i(g,!0);if(f)return f(g,!0);throw i=Error("Cannot find module '"+g+"'"),i.code="MODULE_NOT_FOUND",i}i=c[g]={exports:{}},b[g][0].call(i.exports,function(a){var c=b[g][1][a];return e(c?c:a)},i,i.exports,a,b,c,d)}return c[g].exports}for(var f="function"==typeof require&&require,g=0;g<d.length;g++)e(d[g]);return e}({1:[function(a,b,c){var d=a("merge");(b.exports=function(a){this.top=a.top,this.left=a.left,this.width=a.width,this.spacing=a.spacing,this.targetRowHeight=a.targetRowHeight,this.targetRowHeightTolerance=a.targetRowHeightTolerance,this.minAspectRatio=this.width/a.targetRowHeight*(1-a.targetRowHeightTolerance),this.maxAspectRatio=this.width/a.targetRowHeight*(1+a.targetRowHeightTolerance),this.edgeCaseMinRowHeight=a.edgeCaseMinRowHeight||Number.NEGATIVE_INFINITY,this.edgeCaseMaxRowHeight=a.edgeCaseMaxRowHeight||Number.POSITIVE_INFINITY,this.rightToLeft=a.rightToLeft,this.isBreakoutRow=a.isBreakoutRow,this.items=[],this.height=0}).prototype={addItem:function(a){var f,g,h,b=this.items.concat(a),c=this.width-(b.length-1)*this.spacing,b=b.reduce(function(a,b){return a+b.aspectRatio},0),e=c/this.targetRowHeight;return this.isBreakoutRow&&0===this.items.length&&1<=a.aspectRatio?(this.items.push(a),this.completeLayout(c/a.aspectRatio),!0):0!==b&&(b<this.minAspectRatio?(this.items.push(d(a)),!0):b>this.maxAspectRatio?0===this.items.length?(this.items.push(d(a)),this.completeLayout(c/b),!0):(f=this.width-(this.items.length-1)*this.spacing,g=this.items.reduce(function(a,b){return a+b.aspectRatio},0),h=f/this.targetRowHeight,Math.abs(b-e)>Math.abs(g-h)?(this.completeLayout(f/g),!1):(this.items.push(d(a)),this.completeLayout(c/b),!0)):(this.items.push(d(a)),this.completeLayout(c/b),!0))},isLayoutComplete:function(){return 0<this.height},completeLayout:function(a,b){var e,f,g,h,i,c=this.rightToLeft?-this.left:this.left,d=this.width-(this.items.length-1)*this.spacing,j=this;"undefined"==typeof b&&(b=!0),f=Math.round(a),g=Math.max(this.edgeCaseMinRowHeight,Math.min(f,this.edgeCaseMaxRowHeight)),f!==g?(this.height=g,e=d/g/(d/f)):(this.height=f,e=1),this.items.forEach(function(a,b){a.top=j.top,a.width=Math.round(a.aspectRatio*j.height*e),a.height=j.height,a.left=j.rightToLeft?j.width-c-a.width:c,c+=a.width+j.spacing}),b&&(this.rightToLeft||(c-=this.spacing+this.left),h=(c-this.width)/this.items.length,i=this.items.map(function(a,b){return Math.round((b+1)*h)}),1===this.items.length?(d=this.items[0],d.width-=Math.round(h),this.rightToLeft&&(d.left+=Math.round(h))):this.items.forEach(function(a,b){0<b?(a.left-=i[b-1],a.width-=i[b]-i[b-1]):a.width-=i[b]}))},forceComplete:function(a,b){var c=this.width-(this.items.length-1)*this.spacing,d=this.items.reduce(function(a,b){return a+b.aspectRatio},0);"number"==typeof b?this.completeLayout(b,!1):a?this.completeLayout(c/d):this.completeLayout(this.targetRowHeight,!1)},getItems:function(){return this.items}}},{merge:2}],2:[function(a,b,c){!function(a){function c(a,b){if("object"!==e(a))return b;for(var d in b)"object"===e(a[d])&&"object"===e(b[d])?a[d]=c(a[d],b[d]):a[d]=b[d];return a}function d(a,b,d){var g=d[0],h=d.length;(a||"object"!==e(g))&&(g={});for(var i=0;i<h;++i){var j=d[i];if("object"===e(j))for(var k in j){var l=a?f.clone(j[k]):j[k];g[k]=b?c(g[k],l):l}}return g}function e(a){return{}.toString.call(a).slice(8,-1).toLowerCase()}var f=function(a){return d(!0===a,!1,arguments)};f.recursive=function(a){return d(!0===a,!0,arguments)},f.clone=function(a){var d,b=a,c=e(a);if("array"===c)for(b=[],c=a.length,d=0;d<c;++d)b[d]=f.clone(a[d]);else if("object"===c)for(d in b={},a)b[d]=f.clone(a[d]);return b},a?b.exports=f:window.merge=f}("object"==typeof b&&b&&"object"==typeof b.exports&&b.exports)},{}],"justified-layout":[function(a,b,c){function d(a){var c,d,b=[];return i.forceAspectRatio&&a.forEach(function(a){a.forcedAspectRatio=!0,a.aspectRatio=i.forceAspectRatio}),a.some(function(a,g){if(d||(d=e()),c=d.addItem(a),d.isLayoutComplete()){if(b=b.concat(f(d)),j._rows.length>=i.maxNumRows)return d=null,!0;if(d=e(),!c&&(c=d.addItem(a),d.isLayoutComplete())){if(b=b.concat(f(d)),j._rows.length>=i.maxNumRows)return d=null,!0;d=e()}}}),d&&d.getItems().length&&i.showWidows&&(j._rows.length?(a=j._rows[j._rows.length-1].isBreakoutRow?j._rows[j._rows.length-1].targetRowHeight:j._rows[j._rows.length-1].height,d.forceComplete(!1,a||i.targetRowHeight)):d.forceComplete(!1),b=b.concat(f(d))),j._containerHeight-=i.boxSpacing.vertical||i.boxSpacing,j._containerHeight+=i.containerPadding.bottom||i.containerPadding,{containerHeight:j._containerHeight,boxes:j._layoutItems}}function e(){if(!1!==i.fullWidthBreakoutRowCadence&&0===(j._rows.length+1)%i.fullWidthBreakoutRowCadence)var a=!0;return new h({top:j._containerHeight,left:i.containerPadding.left,width:i.containerWidth-i.containerPadding.left-i.containerPadding.right,spacing:i.boxSpacing.horizontal,targetRowHeight:i.targetRowHeight,targetRowHeightTolerance:i.targetRowHeightTolerance,edgeCaseMinRowHeight:.5*i.targetRowHeight,edgeCaseMaxRowHeight:2*i.targetRowHeight,rightToLeft:!1,isBreakoutRow:a})}function f(a){return j._rows.push(a),j._layoutItems=j._layoutItems.concat(a.getItems()),j._containerHeight+=a.height+i.boxSpacing.vertical,a.items}var g=a("merge"),h=a("./row"),i={},j={};b.exports=function(a){i=g({containerWidth:1060,containerPadding:10,boxSpacing:10,targetRowHeight:320,targetRowHeightTolerance:.25,maxNumRows:Number.POSITIVE_INFINITY,forceAspectRatio:!1,showWidows:!0,fullWidthBreakoutRowCadence:!1},1>=arguments.length||void 0===arguments[1]?{}:arguments[1]);var b={},c={};return b.top=isNaN(parseFloat(i.containerPadding.top))?i.containerPadding:i.containerPadding.top,b.right=isNaN(parseFloat(i.containerPadding.right))?i.containerPadding:i.containerPadding.right,b.bottom=isNaN(parseFloat(i.containerPadding.bottom))?i.containerPadding:i.containerPadding.bottom,b.left=isNaN(parseFloat(i.containerPadding.left))?i.containerPadding:i.containerPadding.left,c.horizontal=isNaN(parseFloat(i.boxSpacing.horizontal))?i.boxSpacing:i.boxSpacing.horizontal,c.vertical=isNaN(parseFloat(i.boxSpacing.vertical))?i.boxSpacing:i.boxSpacing.vertical,i.containerPadding=b,i.boxSpacing=c,j._layoutItems=[],j._awakeItems=[],j._inViewportItems=[],j._leadingOrphans=[],j._trailingOrphans=[],j._containerHeight=i.containerPadding.top,j._rows=[],j._orphans=[],d(a.map(function(a){return a.width&&a.width?{aspectRatio:a.width/a.height}:{aspectRatio:a}}))}},{"./row":1,merge:2}]},{},[]);

/*! highlight.js v9.9.0 | BSD3 License | git.io/hljslicense */
!function(e){var n="object"==typeof window&&window||"object"==typeof self&&self;"undefined"!=typeof exports?e(exports):n&&(n.hljs=e({}),"function"==typeof define&&define.amd&&define([],function(){return n.hljs}))}(function(e){function n(e){return e.replace(/[&<>]/gm,function(e){return I[e]})}function t(e){return e.nodeName.toLowerCase()}function r(e,n){var t=e&&e.exec(n);return t&&0===t.index}function i(e){return k.test(e)}function a(e){var n,t,r,a,o=e.className+" ";if(o+=e.parentNode?e.parentNode.className:"",t=B.exec(o))return R(t[1])?t[1]:"no-highlight";for(o=o.split(/\s+/),n=0,r=o.length;r>n;n++)if(a=o[n],i(a)||R(a))return a}function o(e,n){var t,r={};for(t in e)r[t]=e[t];if(n)for(t in n)r[t]=n[t];return r}function u(e){var n=[];return function r(e,i){for(var a=e.firstChild;a;a=a.nextSibling)3===a.nodeType?i+=a.nodeValue.length:1===a.nodeType&&(n.push({event:"start",offset:i,node:a}),i=r(a,i),t(a).match(/br|hr|img|input/)||n.push({event:"stop",offset:i,node:a}));return i}(e,0),n}function c(e,r,i){function a(){return e.length&&r.length?e[0].offset!==r[0].offset?e[0].offset<r[0].offset?e:r:"start"===r[0].event?e:r:e.length?e:r}function o(e){function r(e){return" "+e.nodeName+'="'+n(e.value)+'"'}l+="<"+t(e)+w.map.call(e.attributes,r).join("")+">"}function u(e){l+="</"+t(e)+">"}function c(e){("start"===e.event?o:u)(e.node)}for(var s=0,l="",f=[];e.length||r.length;){var g=a();if(l+=n(i.substring(s,g[0].offset)),s=g[0].offset,g===e){f.reverse().forEach(u);do c(g.splice(0,1)[0]),g=a();while(g===e&&g.length&&g[0].offset===s);f.reverse().forEach(o)}else"start"===g[0].event?f.push(g[0].node):f.pop(),c(g.splice(0,1)[0])}return l+n(i.substr(s))}function s(e){function n(e){return e&&e.source||e}function t(t,r){return new RegExp(n(t),"m"+(e.cI?"i":"")+(r?"g":""))}function r(i,a){if(!i.compiled){if(i.compiled=!0,i.k=i.k||i.bK,i.k){var u={},c=function(n,t){e.cI&&(t=t.toLowerCase()),t.split(" ").forEach(function(e){var t=e.split("|");u[t[0]]=[n,t[1]?Number(t[1]):1]})};"string"==typeof i.k?c("keyword",i.k):E(i.k).forEach(function(e){c(e,i.k[e])}),i.k=u}i.lR=t(i.l||/\w+/,!0),a&&(i.bK&&(i.b="\\b("+i.bK.split(" ").join("|")+")\\b"),i.b||(i.b=/\B|\b/),i.bR=t(i.b),i.e||i.eW||(i.e=/\B|\b/),i.e&&(i.eR=t(i.e)),i.tE=n(i.e)||"",i.eW&&a.tE&&(i.tE+=(i.e?"|":"")+a.tE)),i.i&&(i.iR=t(i.i)),null==i.r&&(i.r=1),i.c||(i.c=[]);var s=[];i.c.forEach(function(e){e.v?e.v.forEach(function(n){s.push(o(e,n))}):s.push("self"===e?i:e)}),i.c=s,i.c.forEach(function(e){r(e,i)}),i.starts&&r(i.starts,a);var l=i.c.map(function(e){return e.bK?"\\.?("+e.b+")\\.?":e.b}).concat([i.tE,i.i]).map(n).filter(Boolean);i.t=l.length?t(l.join("|"),!0):{exec:function(){return null}}}}r(e)}function l(e,t,i,a){function o(e,n){var t,i;for(t=0,i=n.c.length;i>t;t++)if(r(n.c[t].bR,e))return n.c[t]}function u(e,n){if(r(e.eR,n)){for(;e.endsParent&&e.parent;)e=e.parent;return e}return e.eW?u(e.parent,n):void 0}function c(e,n){return!i&&r(n.iR,e)}function g(e,n){var t=N.cI?n[0].toLowerCase():n[0];return e.k.hasOwnProperty(t)&&e.k[t]}function h(e,n,t,r){var i=r?"":y.classPrefix,a='<span class="'+i,o=t?"":C;return a+=e+'">',a+n+o}function p(){var e,t,r,i;if(!E.k)return n(B);for(i="",t=0,E.lR.lastIndex=0,r=E.lR.exec(B);r;)i+=n(B.substring(t,r.index)),e=g(E,r),e?(M+=e[1],i+=h(e[0],n(r[0]))):i+=n(r[0]),t=E.lR.lastIndex,r=E.lR.exec(B);return i+n(B.substr(t))}function d(){var e="string"==typeof E.sL;if(e&&!x[E.sL])return n(B);var t=e?l(E.sL,B,!0,L[E.sL]):f(B,E.sL.length?E.sL:void 0);return E.r>0&&(M+=t.r),e&&(L[E.sL]=t.top),h(t.language,t.value,!1,!0)}function b(){k+=null!=E.sL?d():p(),B=""}function v(e){k+=e.cN?h(e.cN,"",!0):"",E=Object.create(e,{parent:{value:E}})}function m(e,n){if(B+=e,null==n)return b(),0;var t=o(n,E);if(t)return t.skip?B+=n:(t.eB&&(B+=n),b(),t.rB||t.eB||(B=n)),v(t,n),t.rB?0:n.length;var r=u(E,n);if(r){var i=E;i.skip?B+=n:(i.rE||i.eE||(B+=n),b(),i.eE&&(B=n));do E.cN&&(k+=C),E.skip||(M+=E.r),E=E.parent;while(E!==r.parent);return r.starts&&v(r.starts,""),i.rE?0:n.length}if(c(n,E))throw new Error('Illegal lexeme "'+n+'" for mode "'+(E.cN||"<unnamed>")+'"');return B+=n,n.length||1}var N=R(e);if(!N)throw new Error('Unknown language: "'+e+'"');s(N);var w,E=a||N,L={},k="";for(w=E;w!==N;w=w.parent)w.cN&&(k=h(w.cN,"",!0)+k);var B="",M=0;try{for(var I,j,O=0;;){if(E.t.lastIndex=O,I=E.t.exec(t),!I)break;j=m(t.substring(O,I.index),I[0]),O=I.index+j}for(m(t.substr(O)),w=E;w.parent;w=w.parent)w.cN&&(k+=C);return{r:M,value:k,language:e,top:E}}catch(T){if(T.message&&-1!==T.message.indexOf("Illegal"))return{r:0,value:n(t)};throw T}}function f(e,t){t=t||y.languages||E(x);var r={r:0,value:n(e)},i=r;return t.filter(R).forEach(function(n){var t=l(n,e,!1);t.language=n,t.r>i.r&&(i=t),t.r>r.r&&(i=r,r=t)}),i.language&&(r.second_best=i),r}function g(e){return y.tabReplace||y.useBR?e.replace(M,function(e,n){return y.useBR&&"\n"===e?"<br>":y.tabReplace?n.replace(/\t/g,y.tabReplace):void 0}):e}function h(e,n,t){var r=n?L[n]:t,i=[e.trim()];return e.match(/\bhljs\b/)||i.push("hljs"),-1===e.indexOf(r)&&i.push(r),i.join(" ").trim()}function p(e){var n,t,r,o,s,p=a(e);i(p)||(y.useBR?(n=document.createElementNS("http://www.w3.org/1999/xhtml","div"),n.innerHTML=e.innerHTML.replace(/\n/g,"").replace(/<br[ \/]*>/g,"\n")):n=e,s=n.textContent,r=p?l(p,s,!0):f(s),t=u(n),t.length&&(o=document.createElementNS("http://www.w3.org/1999/xhtml","div"),o.innerHTML=r.value,r.value=c(t,u(o),s)),r.value=g(r.value),e.innerHTML=r.value,e.className=h(e.className,p,r.language),e.result={language:r.language,re:r.r},r.second_best&&(e.second_best={language:r.second_best.language,re:r.second_best.r}))}function d(e){y=o(y,e)}function b(){if(!b.called){b.called=!0;var e=document.querySelectorAll("pre code");w.forEach.call(e,p)}}function v(){addEventListener("DOMContentLoaded",b,!1),addEventListener("load",b,!1)}function m(n,t){var r=x[n]=t(e);r.aliases&&r.aliases.forEach(function(e){L[e]=n})}function N(){return E(x)}function R(e){return e=(e||"").toLowerCase(),x[e]||x[L[e]]}var w=[],E=Object.keys,x={},L={},k=/^(no-?highlight|plain|text)$/i,B=/\blang(?:uage)?-([\w-]+)\b/i,M=/((^(<[^>]+>|\t|)+|(?:\n)))/gm,C="</span>",y={classPrefix:"hljs-",tabReplace:null,useBR:!1,languages:void 0},I={"&":"&amp;","<":"&lt;",">":"&gt;"};return e.highlight=l,e.highlightAuto=f,e.fixMarkup=g,e.highlightBlock=p,e.configure=d,e.initHighlighting=b,e.initHighlightingOnLoad=v,e.registerLanguage=m,e.listLanguages=N,e.getLanguage=R,e.inherit=o,e.IR="[a-zA-Z]\\w*",e.UIR="[a-zA-Z_]\\w*",e.NR="\\b\\d+(\\.\\d+)?",e.CNR="(-?)(\\b0[xX][a-fA-F0-9]+|(\\b\\d+(\\.\\d*)?|\\.\\d+)([eE][-+]?\\d+)?)",e.BNR="\\b(0b[01]+)",e.RSR="!|!=|!==|%|%=|&|&&|&=|\\*|\\*=|\\+|\\+=|,|-|-=|/=|/|:|;|<<|<<=|<=|<|===|==|=|>>>=|>>=|>=|>>>|>>|>|\\?|\\[|\\{|\\(|\\^|\\^=|\\||\\|=|\\|\\||~",e.BE={b:"\\\\[\\s\\S]",r:0},e.ASM={cN:"string",b:"'",e:"'",i:"\\n",c:[e.BE]},e.QSM={cN:"string",b:'"',e:'"',i:"\\n",c:[e.BE]},e.PWM={b:/\b(a|an|the|are|I'm|isn't|don't|doesn't|won't|but|just|should|pretty|simply|enough|gonna|going|wtf|so|such|will|you|your|like)\b/},e.C=function(n,t,r){var i=e.inherit({cN:"comment",b:n,e:t,c:[]},r||{});return i.c.push(e.PWM),i.c.push({cN:"doctag",b:"(?:TODO|FIXME|NOTE|BUG|XXX):",r:0}),i},e.CLCM=e.C("//","$"),e.CBCM=e.C("/\\*","\\*/"),e.HCM=e.C("#","$"),e.NM={cN:"number",b:e.NR,r:0},e.CNM={cN:"number",b:e.CNR,r:0},e.BNM={cN:"number",b:e.BNR,r:0},e.CSSNM={cN:"number",b:e.NR+"(%|em|ex|ch|rem|vw|vh|vmin|vmax|cm|mm|in|pt|pc|px|deg|grad|rad|turn|s|ms|Hz|kHz|dpi|dpcm|dppx)?",r:0},e.RM={cN:"regexp",b:/\//,e:/\/[gimuy]*/,i:/\n/,c:[e.BE,{b:/\[/,e:/\]/,r:0,c:[e.BE]}]},e.TM={cN:"title",b:e.IR,r:0},e.UTM={cN:"title",b:e.UIR,r:0},e.METHOD_GUARD={b:"\\.\\s*"+e.UIR,r:0},e});hljs.registerLanguage("xml",function(s){var e="[A-Za-z0-9\\._:-]+",t={eW:!0,i:/</,r:0,c:[{cN:"attr",b:e,r:0},{b:/=\s*/,r:0,c:[{cN:"string",endsParent:!0,v:[{b:/"/,e:/"/},{b:/'/,e:/'/},{b:/[^\s"'=<>`]+/}]}]}]};return{aliases:["html","xhtml","rss","atom","xjb","xsd","xsl","plist"],cI:!0,c:[{cN:"meta",b:"<!DOCTYPE",e:">",r:10,c:[{b:"\\[",e:"\\]"}]},s.C("<!--","-->",{r:10}),{b:"<\\!\\[CDATA\\[",e:"\\]\\]>",r:10},{b:/<\?(php)?/,e:/\?>/,sL:"php",c:[{b:"/\\*",e:"\\*/",skip:!0}]},{cN:"tag",b:"<style(?=\\s|>|$)",e:">",k:{name:"style"},c:[t],starts:{e:"</style>",rE:!0,sL:["css","xml"]}},{cN:"tag",b:"<script(?=\\s|>|$)",e:">",k:{name:"script"},c:[t],starts:{e:"</script>",rE:!0,sL:["actionscript","javascript","handlebars","xml"]}},{cN:"meta",v:[{b:/<\?xml/,e:/\?>/,r:10},{b:/<\?\w+/,e:/\?>/}]},{cN:"tag",b:"</?",e:"/?>",c:[{cN:"name",b:/[^\/><\s]+/,r:0},t]}]}});hljs.registerLanguage("javascript",function(e){var r="[A-Za-z$_][0-9A-Za-z$_]*",t={keyword:"in of if for while finally var new function do return void else break catch instanceof with throw case default try this switch continue typeof delete let yield const export super debugger as async await static import from as",literal:"true false null undefined NaN Infinity",built_in:"eval isFinite isNaN parseFloat parseInt decodeURI decodeURIComponent encodeURI encodeURIComponent escape unescape Object Function Boolean Error EvalError InternalError RangeError ReferenceError StopIteration SyntaxError TypeError URIError Number Math Date String RegExp Array Float32Array Float64Array Int16Array Int32Array Int8Array Uint16Array Uint32Array Uint8Array Uint8ClampedArray ArrayBuffer DataView JSON Intl arguments require module console window document Symbol Set Map WeakSet WeakMap Proxy Reflect Promise"},a={cN:"number",v:[{b:"\\b(0[bB][01]+)"},{b:"\\b(0[oO][0-7]+)"},{b:e.CNR}],r:0},n={cN:"subst",b:"\\$\\{",e:"\\}",k:t,c:[]},c={cN:"string",b:"`",e:"`",c:[e.BE,n]};n.c=[e.ASM,e.QSM,c,a,e.RM];var s=n.c.concat([e.CBCM,e.CLCM]);return{aliases:["js","jsx"],k:t,c:[{cN:"meta",r:10,b:/^\s*['"]use (strict|asm)['"]/},{cN:"meta",b:/^#!/,e:/$/},e.ASM,e.QSM,c,e.CLCM,e.CBCM,a,{b:/[{,]\s*/,r:0,c:[{b:r+"\\s*:",rB:!0,r:0,c:[{cN:"attr",b:r,r:0}]}]},{b:"("+e.RSR+"|\\b(case|return|throw)\\b)\\s*",k:"return throw case",c:[e.CLCM,e.CBCM,e.RM,{cN:"function",b:"(\\(.*?\\)|"+r+")\\s*=>",rB:!0,e:"\\s*=>",c:[{cN:"params",v:[{b:r},{b:/\(\s*\)/},{b:/\(/,e:/\)/,eB:!0,eE:!0,k:t,c:s}]}]},{b:/</,e:/(\/\w+|\w+\/)>/,sL:"xml",c:[{b:/<\w+\s*\/>/,skip:!0},{b:/<\w+/,e:/(\/\w+|\w+\/)>/,skip:!0,c:[{b:/<\w+\s*\/>/,skip:!0},"self"]}]}],r:0},{cN:"function",bK:"function",e:/\{/,eE:!0,c:[e.inherit(e.TM,{b:r}),{cN:"params",b:/\(/,e:/\)/,eB:!0,eE:!0,c:s}],i:/\[|%/},{b:/\$[(.]/},e.METHOD_GUARD,{cN:"class",bK:"class",e:/[{;=]/,eE:!0,i:/[:"\[\]]/,c:[{bK:"extends"},e.UTM]},{bK:"constructor",e:/\{/,eE:!0}],i:/#(?!!)/}});
/* jshint ignore:end */
		
(function() {
  
    window.cube3D = function() {

        var xm         = 0,
            ym         = 0,
            cx         = 50,
            cy         = 50,
            cz         = 0,
            cxb        = 0,
            cyb        = 0,
            startX     = 0,
            startY     = 0,
            angleY     = 0,
            angleX     = 0,
            angleZ     = 0,
            autorotate = true,
            running    = true,
            scr, canvas, cubes, faces, nx, ny, nw, nh,
            cosY, sinY, cosX, sinX, cosZ, sinZ, minZ,
            ncube, npoly, drag, moved;

        // ---- fov ---- 
        var fl = 350;
        var zoom = 180;//0

        // ======== canvas constructor ======== 
        var Canvas = function( id ) {

            this.container = document.getElementById( id );
            this.ctx = this.container.getContext( "2d" );

            this.resize = function( w, h ) {

                this.container.width  = w;
                this.container.height = h;

            };

        };

        // ======== vertex constructor ======== 
        var Point = function( parent, xyz, project ) {

            this.project = project;
            this.xo = xyz[ 0 ];
            this.yo = xyz[ 1 ];
            this.zo = xyz[ 2 ];
            this.cube = parent;

        };

        Point.prototype.projection = function() {

            // ---- 3D rotation ---- 
            var x = cosY * ( sinZ * this.yo + cosZ * this.xo ) - sinY * this.zo;
            var y = sinX * ( cosY * this.zo + sinY * ( sinZ * this.yo + cosZ * this.xo ) ) + cosX * ( cosZ * this.yo - sinZ * this.xo );
            var z = cosX * ( cosY * this.zo + sinY * ( sinZ * this.yo + cosZ * this.xo ) ) - sinX * ( cosZ * this.yo - sinZ * this.xo );

            this.x = x;
            this.y = y;
            this.z = z;

            if ( this.project ) {
                // ---- point visible ---- 
                if ( z < minZ ) minZ = z;
                this.visible = ( zoom + z > 0 );
                // ---- 3D to 2D projection ---- 
                this.X = ( nw * 0.5 ) + x * ( fl / ( z + zoom ) );
                this.Y = ( nh * 0.5 ) + y * ( fl / ( z + zoom ) );
            }

        };

        // ======= polygon constructor ======== 
        var Face = function( cube, index, normalVector ) {

            // ---- parent cube ---- 
            this.cube = cube;

            // ---- coordinates ---- 
            this.p0 = cube.points[ index[ 0 ] ];
            this.p1 = cube.points[ index[ 1 ] ];
            this.p2 = cube.points[ index[ 2 ] ];
            this.p3 = cube.points[ index[ 3 ] ];

            // ---- normal vector ---- 
            this.normal = new Point( this, normalVector, false );

        };

        Face.prototype.pointerInside = function() {

            // ---- Is Point Inside Triangle? ---- 
            var fAB = function( p1, p2, p3 ) {
                return ( ym - p1.Y ) * ( p2.X - p1.X ) - ( xm - p1.X ) * ( p2.Y - p1.Y );
            };

            var fCA = function( p1, p2, p3 ) {
                return ( ym - p3.Y ) * ( p1.X - p3.X ) - ( xm - p3.X ) * ( p1.Y - p3.Y );
            };

            var fBC = function( p1, p2, p3 ) {
                return ( ym - p2.Y ) * ( p3.X - p2.X ) - ( xm - p2.X ) * ( p3.Y - p2.Y );
            };

            if (
                fAB( this.p0, this.p1, this.p3 ) * fBC( this.p0, this.p1, this.p3 ) > 0 &&
                fBC( this.p0, this.p1, this.p3 ) * fCA( this.p0, this.p1, this.p3 ) > 0
            ) {
                return true;
            }

            if (
                fAB( this.p1, this.p2, this.p3 ) * fBC( this.p1, this.p2, this.p3 ) > 0 &&
                fBC( this.p1, this.p2, this.p3 ) * fCA( this.p1, this.p2, this.p3 ) > 0
            ) {
                return true;
            }

            return false;

        };

        Face.prototype.faceVisible = function() {

            // ---- points visible ---- 
            if ( this.p0.visible && this.p1.visible && this.p2.visible && this.p3.visible ) {

                // ---- back face culling ---- 
                /*jslint bitwise: true */
                if ( ( this.p1.Y - this.p0.Y ) / ( this.p1.X - this.p0.X ) < ( this.p2.Y - this.p0.Y ) / ( this.p2.X - this.p0.X ) ^ this.p0.X < this.p1.X == this.p0.X > this.p2.X ) {
                    // ---- face visible ---- 
                    this.visible = true;
                    return true;
                }

            }

            // ---- face hidden ---- 
            this.visible = false;
            this.distance = -9999;
            return false;
        };

        Face.prototype.distanceToCamera = function() {

            // ---- distance to camera ---- 
            var dx = ( this.p0.x + this.p1.x + this.p2.x + this.p3.x ) * 0.025;
            var dy = ( this.p0.y + this.p1.y + this.p2.y + this.p3.y ) * 0.025;
            var dz = ( this.p0.z + this.p1.z + this.p2.z + this.p3.z ) * 0.025;

            this.distance = Math.sqrt( dx * dx + dy * dy + dz * dz );

        };

        Face.prototype.draw = function() {

            // ---- shape face ---- 
            canvas.ctx.beginPath();
            canvas.ctx.moveTo( this.p0.X, this.p0.Y );
            canvas.ctx.lineTo( this.p1.X, this.p1.Y );
            canvas.ctx.lineTo( this.p2.X, this.p2.Y );
            canvas.ctx.lineTo( this.p3.X, this.p3.Y );
            canvas.ctx.closePath();

            this.normal.projection();

            var r = Math.sqrt( this.normal.z ) * 300,
                g = r,
                b = r;

            // ---- fill ---- 
            canvas.ctx.fillStyle = "rgba(" +
                Math.round( r ) + "," +
                Math.round( g ) + "," +
                Math.round( b ) + "," + this.cube.alpha + ")";
            canvas.ctx.fill();


        };

        // ======== Cube constructor ======== 
        var Cube = function( parent, nx, ny, nz, x, y, z, w ) {
            
            var i = 0, p;
            
            if ( parent ) {

                // ---- translate parent points ---- 
                this.w = parent.w;
                this.points = [];
                

                while ( !!( p = parent.points[ i++ ] ) ) {

                    this.points.push(

                        new Point(
                            parent, [ p.xo + nx, p.yo + ny, p.zo + nz ],
                            true
                        )

                    );

                }

            } else {

                // ---- create points ---- 
                this.w = w;
                this.points = [];
                p = [
                    [ x - w, y - w, z - w ],
                    [ x + w, y - w, z - w ],
                    [ x + w, y + w, z - w ],
                    [ x - w, y + w, z - w ],
                    [ x - w, y - w, z + w ],
                    [ x + w, y - w, z + w ],
                    [ x + w, y + w, z + w ],
                    [ x - w, y + w, z + w ]
                ];

                for ( i in p ) {
                    
                    if ( p.hasOwnProperty( i ) ) {
                        this.points.push( new Point( this, p[ i ], true ) );
                    }
                
                }

            }

            // ---- faces coordinates ---- 
            var f = [
                [ 0, 1, 2, 3 ],
                [ 0, 4, 5, 1 ],
                [ 3, 2, 6, 7 ],
                [ 0, 3, 7, 4 ],
                [ 1, 5, 6, 2 ],
                [ 5, 4, 7, 6 ]
            ];

            // ---- faces normals ---- 
            var nv = [
                [ 0, 0, 1 ],
                [ 0, 1, 0 ],
                [ 0, -1, 0 ],
                [ 1, 0, 0 ],
                [ -1, 0, 0 ],
                [ 0, 0, -1 ]
            ];

            // ---- cube transparency ---- 
            this.alpha = 1;

            // ---- push faces ---- 
            for ( i in f ) {
                
                if ( f.hasOwnProperty( i ) ) {
                    
                    faces.push(
                        new Face( this, f[ i ], nv[ i ] )
                    );
                    
                }
                
            }

            faces[0].top = true;

            ncube++;

        };

        var resize = function() {

            // ---- screen resize ---- 
            nw = scr.offsetWidth;
            nh = scr.offsetHeight;

            var o = scr;
			var docH = window.innerHeight;

            for ( nx = 0, ny = 0; o !== null; o = o.offsetParent ) {
                nx += o.offsetLeft;
                ny += o.offsetTop;
            }

			fl = Math.min( nw * 0.385, nh * 0.76 );
            var shadow = document.getElementById( 'cube-shadow' );

            shadow.style.width  = fl * 1.4 + 'px';
            shadow.style.height = fl * 1.4 + 'px';

            canvas.resize( nw, nh );

        };

        var reset = function() {
            // ---- create first cube ---- 
            cubes = [];
            faces = [];
            ncube = 0;
            npoly = 0;
            cubes.push(
                new Cube( false, 0, 0, 0, 0, 0, 0, 50 )
            );
        };

        var detectFaceOver = function() {};
        var click = function() {};

        var init = function() {

                // ---- init script ---- 
                if ( ! document.getElementById( "cube" ) ) {
                    return false;
                }
			
				scr = document.getElementById( "cube-holder"/*hero"*/ );
                canvas = new Canvas( "cube" );

                // ======== unified touch/mouse events handler ======== 
                scr.onmousedown = function( e ) {
                    if ( !running ) return true;
                    // ---- touchstart ---- 
                    if ( e.target !== canvas.container ) return;
                    e.preventDefault(); // prevents scrolling 
                    if ( scr.setCapture ) scr.setCapture();
                    moved = false;
                    drag = true;
                    startX = ( e.clientX !== undefined ? e.clientX : e.touches[ 0 ].clientX ) - nx;
                    startY = ( e.clientY !== undefined ? e.clientY : e.touches[ 0 ].clientY ) - ny;
                };

                scr.onmousemove = function( e ) {
                    if ( e.which === 3) return;
                    if ( !running ) return true;
                    // ---- touchmove ---- 
                    e.preventDefault();
                    xm = ( e.clientX !== undefined ? e.clientX : e.touches[ 0 ].clientX ) - nx;
                    ym = ( e.clientY !== undefined ? e.clientY : e.touches[ 0 ].clientY ) - ny;
                    detectFaceOver();
                    if ( drag ) {
                        cx = cxb + ( xm - startX );
                        cy = cyb - ( ym - startY );
                    }
                    if ( Math.abs( xm - startX ) > 10 || Math.abs( ym - startY ) > 10 ) {
                        // ---- if pointer moves then cancel the tap/click ---- 
                        moved = true;
                    }
                };

                window.onmouseup = function( e ) {
                    if ( !running ) return true;
                    // ---- touchend ---- 
                    e.preventDefault();
                    if ( scr.releaseCapture ) scr.releaseCapture();
                    drag = false;
                    cxb = cx;
                    cyb = cy;
                    if ( !moved ) {
                        // ---- click/tap ---- 
                        xm = startX;
                        ym = startY;
                        click();
                    }
                };
            
                

                // ---- screen size ---- 
                resize();

                window.addEventListener( 'resize', resize, false );

                // ---- engine start ---- 
                reset();
                run();
            
                window.addEventListener( 'scroll', function() {

                    if (window.pageYOffset >= window.innerHeight) {
                        
                        if (window.cubeRAF) {
                            cancelAnimationFrame(window.cubeRAF);
                            window.cubeRAF = false;
                        }
                        
                    } else if (!window.cubeRAF) {
                        
                        run();
                    }
  
                });

            };


        // ======== main loop ======== 
        var run = function() {

            // ---- screen background ---- 
            canvas.ctx.clearRect( 0, 0, 9999, 9999 );

            // ---- easing rotations ---- 
            angleX += ( ( cy - angleX ) * 0.025 );
            angleY += ( ( cx - angleY ) * 0.025 );
            angleZ += ( ( cz - angleZ ) * 0.025 );
            if ( autorotate ) cz += 1;

            // ---- pre-calculating trigo ---- 
            cosY = Math.cos( angleY * 0.01 );
            sinY = Math.sin( angleY * 0.01 );
            cosX = Math.cos( angleX * 0.01 );
            sinX = Math.sin( angleX * 0.01 );
            cosZ = Math.cos( angleZ * 0.01 );
            sinZ = Math.sin( angleZ * 0.01 );

            // ---- points projection ---- 
            minZ = 0;
            var i = 0,
                j, c, p;
            while ( !!( c = cubes[ i++ ] ) ) {
                j = 0;
                while ( !!( p = c.points[ j++ ] ) ) {
                    p.projection();
                }
            }
            // ---- adapt zoom ---- 
            var d = -minZ + 100 - zoom;
            zoom += ( d * ( ( d > 0 ) ? 0.05 : 0.01 ) );

            // ---- faces light ---- 
            j = 0;
            var f;
            while ( !!( f = faces[ j++ ] ) ) {
                if ( f.faceVisible() ) {
                    f.distanceToCamera();
                }
            }
            // ---- faces depth sorting ---- 
            faces.sort( function( p0, p1 ) {
                return p1.distance - p0.distance;
            } );
            // ---- painting faces ---- 
            j = 0;

            while ( !!( f = faces[ j++ ] ) ) {
                if ( f.visible ) {
                    f.draw();
                } else break;
            }

            // ---- animation loop ---- 
            window.cubeRAF = requestAnimationFrame( run );

        };

        init();

    };

 
    var string = 'MODULOBOX By Themeone',
        style1, style2,
        ua   = window.navigator.userAgent,
        msie = ua.indexOf('MSIE ');
        msie = msie > -1 || ua.indexOf('Trident/');
        msie = msie > -1 || ua.indexOf('Edge/');
            
    if ( msie < 0 ) {

        string = '%c' + 'MODULOBOX' + '\n' + '%c' + 'By Themeone';
                
        style1 =
            'color: #DE496D;' +
            'font-size: 22px;' +
            'font-weight: 100;' +
            'line-height: 22px;' +
            'font-family: "Lucida Sans Unicode", "Lucida Grande", sans-serif;' +
            'background: -webkit-gradient(linear, left center, right center, from(#DE496D), to(#FFAE27));' +
            '-webkit-background-clip: text;' +
            'background-clip: text;' +
            '-webkit-text-fill-color: transparent;' +
            'text-fill-color: transparent;'
        ;

        style2 =
            'color: #1C3653;' +
            'font-size: 14px;' +
            'line-height: 16px;' +
            'font-family: "Lucida Sans Unicode", "Lucida Grande", sans-serif;'
        ;
 
        setTimeout( console.log.bind( console, string, style1, style2 ), 0 );

		// prevent issue on IOS because of IE CSS trick for svh height (100vh)...
		var svg = document.getElementsByClassName( 'logo-svg' );
		for (var i = 0, length = svg.length; i < length; i++) {
			svg[i].style.height = 'auto';
		}
   
    } else {

        setTimeout( console.log.bind( console, string ), 0 );
            
    }

    var classReg = function( className ) {
            return new RegExp( '(^|\\s+)' + className + '(\\s+|$)' );
        },
        hasClass = function( el, className ) {
            return !!el.className.match( classReg( className ) );
        },
        addClass = function( el, className ) {
            el.className += !hasClass( el, className ) ? ( el.className ? ' ' : '' )  + className : '';
        },
        removeClass = function( el, className ) {
            el.className = el.className.replace( classReg( className ), ' ' ).replace( /\s+$/, '' );
        };
    
    var anchors = document.querySelectorAll( 'a[href^="#"]' ),
        RAF;
            
    for (var i = 0, length = anchors.length; i < length; i++) {

        anchors[i].addEventListener( 'click', anchor, false );   
                
    }

    function anchor( event ) {
        
        document.documentElement.style.overflow = '';
        removeClass( document.body, 'open-doc' );
        
        if ( !history.pushState ) {
            return;
        }

        event.preventDefault(); 
        event.stopPropagation();

        var id = this.getAttribute( 'href' ),
            el = document.getElementById( id.replace( '#', '' )) ,
            to = el ? Math.round( el.getBoundingClientRect().top - document.body.getBoundingClientRect().top ) : null;

        if (el) {

            to = Math.min( to, document.body.scrollHeight-window.innerHeight );

            smoothScroll( to, 1200 );

            if ( !this.getAttribute( 'data-noanchor' ) ) {
                history.pushState(null, null, id);                
            }

        }

    }
            
    Math.easeOutQuad = function (t, b, c, d) {
        t /= d;
        t--;
        return -c * (t*t*t*t - 1) + b;
    };
            
    // smooth scroll anchor
    function smoothScroll( to, speed ) {
                
        var time,
            start = Date.now(),
            from  = window.pageYOffset;
          
        var loop = function() {
                  
            time = Date.now() - start;
            var change = to - from;
            var val = Math.easeOutQuad(time, from, change, speed);
                    
            if ( Math.round(val) === to || time >= speed * 1.05 ) {

                window.scrollTo( 0, to );
                cancelAnimationFrame( RAF );
                return false;
                        
            }
                    
            window.scrollTo(0, val);
            RAF = requestAnimationFrame( loop );

        };
                
        cancelAnimationFrame( RAF );
        loop(); 
                
    }
            
    // cancel smoothscroll on scroll
    window.addEventListener( 'wheel', cancel, false );
    window.addEventListener( 'touchstart', cancel, false );
    function cancel() {
        cancelAnimationFrame( RAF );
    }

    var navigation    = document.getElementsByTagName( 'header' )[0],
        menuButton    = document.getElementsByClassName('nav-btn')[0],
		docButton     = document.getElementsByClassName( 'doc-btn' )[0],
		expandCodeBtn = document.getElementsByClassName( 'expand-code' ),
        scrollTop     = document.getElementById('scrollTo-top'),
        header        = document.getElementsByTagName( 'header' )[0],
        sideBar       = document.getElementById( 'demos-sidebar' ),
		wordpress     = document.getElementsByClassName( 'wordpress-plugin' )[0],
		javascript    = document.getElementsByClassName( 'javascript-plugin' )[0],
        sideBarHeight = sideBar ? sideBar.clientHeight : 0,
        headerHeight  = header.clientHeight,
        sideBarOffset = window.innerHeight,
		position      = scrollTop.getBoundingClientRect(),
        pageYOffset   = window.pageYOffset;

    var expandCode = function( event ) {

        var parent   = event.target.parentNode;
		var code     = parent.nextElementSibling;
		var height   = code.getElementsByTagName( 'PRE' )[0].clientHeight;
		var expanded = hasClass( code, 'expanded' );
		
		code.style.height = !expanded  ? height + 'px' : '';
		
		if ( expanded ) {
			removeClass( code, 'expanded' ); 
		} else {
			addClass( code, 'expanded' );
		}

    }; 
	
	var scrolllBtn = function(pageYOffset) {

        if ( pageYOffset > 450 ) {
            addClass( scrollTop, 'show' ); 
        } else {
            removeClass( scrollTop, 'show' ); 
        }

    };    
    
    var fixedHeader = function(pageYOffset) {

        if ( pageYOffset >= 20 ) {
            addClass( document.body, 'fixed-header' ); 
        } else {
            removeClass( document.body, 'fixed-header' ); 
        }

    };
    
    var fixedSidebar = function(pageYOffset) {

        if ( sideBar && ( pageYOffset + sideBarHeight + headerHeight - 32 ) >= sideBarOffset ) {
            addClass( document.body, 'fixed-sidebar' ); 
        } else {
            removeClass( document.body, 'fixed-sidebar' ); 
        }

    };
	
	var scrollTopColor = function() {
                
		var element = document.elementFromPoint( position.left, position.top );
		
		if ( !element ) {
			return;
		}

		if ( element.tagName === 'PRE' || element.tagName === 'CODE' || ( typeof element.className === 'string' && ( element.className.indexOf('hljs') > -1 || element.className.indexOf('highlight') > -1 ) ) )  {
			addClass( scrollTop, 'light' );
		} else {
			removeClass( scrollTop, 'light' );
		}  
                
	};
    
    fixedHeader(pageYOffset);
    fixedSidebar(pageYOffset); 
    scrolllBtn(pageYOffset);

    menuButton.addEventListener('click', function( event ){

        event.preventDefault();
        
        if ( hasClass( navigation, 'open-nav' ) ) {
            removeClass( navigation, 'open-nav' );
        } else {
            addClass( navigation, 'open-nav' ); 
        }
         
    });
	
	if ( expandCodeBtn ) {
		
		for ( i = 0; i < expandCodeBtn.length; ++i ) {
			expandCodeBtn[i].addEventListener('click', expandCode, false );
		}
	
	}
	
	if ( wordpress && javascript ) {
		
		wordpress.addEventListener( 'click', function(){

			var princing = document.getElementById( 'pricing' );
			removeClass( princing, 'javascript' );
			addClass( princing, 'wordpress' );

		});

		javascript.addEventListener( 'click', function(){

			var princing = document.getElementById( 'pricing' );
			removeClass( princing, 'wordpress' );
			addClass( princing, 'javascript' );       

		});
		
	}
	
	if ( docButton ) {
		
		docButton.addEventListener( 'click', function( event ){

			event.preventDefault();

			var body = document.body;

			if ( hasClass( body, 'open-doc' ) ) {

				removeClass( body, 'open-doc' );
				document.documentElement.style.overflow = '';

			} else {

				addClass( body, 'open-doc' ); 
				document.documentElement.style.overflow = 'hidden';

			}

		});
		
	}
	
	var userAgent  = navigator.userAgent || navigator.vendor || window.opera;
	var heroheader = document.getElementById( 'hero' );
	var fullHeight = function() {
		if ( /iPad|iPhone|iPod/.test( userAgent ) && heroheader && !docButton ) {
			var height = screen.orientation !== 90  ? window.innerHeight : window.innerWidth;
			heroheader.style.height = height + 'px';
		}
	};
	fullHeight();
	
	window.addEventListener( 'orientationchange', function() {
		fullHeight();
	});
    
    window.addEventListener( 'resize', function(){

        if ( window.innerWidth > 990 && ( hasClass( navigation, 'open-nav' ) || hasClass( document.body, 'open-doc' ) ) ) {
			
            removeClass( navigation, 'open-nav' );
			removeClass( document.body, 'open-doc' );
            document.documentElement.style.overflow = '';
			
        }
        
        addClass( document.body, 'no-transition' );
        removeClass( document.body, 'fixed-sidebar' );
        
        headerHeight  = header.clientHeight;
        pageYOffset   = window.pageYOffset;
        sideBarHeight = sideBar ? sideBar.clientHeight : 0;
        sideBarOffset = window.innerHeight;
		position      = scrollTop.getBoundingClientRect();

        fixedHeader(pageYOffset);
        fixedSidebar(pageYOffset);
        scrolllBtn(pageYOffset);
		scrollTopColor();
        
        removeClass( document.body, 'no-transition' );
  
    });
    
    window.addEventListener( 'scroll', function(){
        
        pageYOffset = window.pageYOffset;
        
		scrollTopColor();
        fixedHeader(pageYOffset);
        fixedSidebar(pageYOffset);
        scrolllBtn(pageYOffset);
  
    });
    
	document.addEventListener( 'DOMContentLoaded', function() {

		var justifiedLayout = require( 'justified-layout' ),
			galls = document.querySelectorAll( '.gallery' ),
			grid  = {
				sizes     : '',
				config    : {
					targetRowHeightTolerance : 0.25,
					targetRowHeight : 240,
					boxSpacing : {
						horizontal : 10,
						vertical   : 10
					},
				}
			};
		
		window.buildGrid = function () {

			var item;

			for (var i = 0; i < galls.length; ++i) {

				var gall = galls[i],
					items = gall.querySelectorAll( 'figure img' );
					grid.sizes = [];
				
				gall.style.visibility = 'hidden';
				
				for (var y = 0; y < items.length; ++y) {

					item  = items[y];
					grid.sizes.push( item.getAttribute( 'width' ) / item.getAttribute( 'height' ) );

				}

				var width = window.innerWidth;
				grid.config.containerWidth  = gall.clientWidth;
				grid.config.targetRowHeight = gall.clientWidth * 0.125;
				grid.config.targetRowHeight = gall.clientWidth > 1920 ? gall.clientWidth * 0.125 : gall.clientWidth * 0.135;
				grid.config.targetRowHeight = gall.clientWidth < 1400 ? gall.clientWidth * 0.175 : grid.config.targetRowHeight;
				grid.config.targetRowHeight = width <= 991  ? gall.clientWidth * 0.375 : grid.config.targetRowHeight;
				grid.config.targetRowHeight = width <= 320  ? gall.clientWidth * 0.750 : grid.config.targetRowHeight;
				grid.config.boxSpacing.horizontal = width <= 480  ? 4 : 10;
				grid.config.boxSpacing.vertical   = width <= 480  ? 4 : 10;

				var geometry = justifiedLayout(grid.sizes, grid.config);

				gall.style.height = geometry.containerHeight + 'px';

				for (var z = 0; z < geometry.boxes.length; ++z) {

					var box = geometry.boxes[z];
					item = items[z].parentElement.parentElement;
					item.style.position = 'absolute';
					item.style.width    = box.width  + 'px';
					item.style.height   = box.height + 'px';
					item.style.left     = box.left   + 'px';
					item.style.top      = box.top    + 'px';

				}
				
				gall.style.visibility = '';
				
			}

		};

		window.cube3D();
		window.buildGrid();
		hljs.initHighlightingOnLoad();
		window.addEventListener( 'resize', window.buildGrid, false );

		setTimeout( function() {
			document.body.className = document.body.className + ' loaded';
		}, 0 );

	});

}());