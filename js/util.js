function isRich(str){
      if(str.match(/<\s?(img|a|p|h1|h2|h3|h4|ul|li|dd|dt)[^>]*>/i)!= undefined) return true;
      if(str.match(/<*(style|font|color)=[^>]*>/i)!= undefined) return true;
      if(str.match('<div attr="rich"></div>')!=undefined) return true;
      return false;
    }
    function HtmlToText(str){
      str = encodeURI(str);
      str = str.replace(/%0A/gi,'');
      str = decodeURI(str);
      str=str.replace(/<div><br*>/gi,'\r\n');
      str=str.replace(/<div>/gi,'\r\n');
      str=str.replace(/<\/div>/gi,'');
      str=str.replace(/<br*>/gi,'\r\n');
      str=str.replace(/<br \/>/gi,'\r\n');
      str=str.replace(/<\s?(span|code|\/span|\/code|cite|\/cite)[^>]*>/gi,'');
      return str;
    }
    function deleterichmark(str){
      str = str.replace(/<div attr=\"rich\"><\/div>/g,'');
      return str;
    }

base64ToURL: function(desc) {
    var temp = document.createElement('div');
    temp.innerHTML = desc;
    var imgs = temp.getElementsByTagName('img');
    for (var i=0, len=imgs.length; i<len; i++) 
      // for compatability: old version(pre1.2) of QN has no dataset['src']
      imgs[i].src = imgs[i].dataset['src'] || imgs[i].src; 
    
    desc = temp.innerHTML;
    temp = imgs = null; // release memory?
    return desc;
  }

  
content: /* (!item.title && !item.desc) ? 'new note' :  */diigo.base64ToURL(isRich(item.desc)?deleterichmark(item.desc):HtmlToText(item.desc)),