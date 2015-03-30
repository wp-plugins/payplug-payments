( function() {
     tinymce.PluginManager.add( 'shortcode_drop', function( editor, url ) {
          
          payplug_shortcodesUrl = url;
          // On remonte d'un repertoire
          payplug_url = payplug_shortcodesUrl.substring(0, payplug_shortcodesUrl.length-2);
          
          var buttonstyle = "background:url('" + payplug_url + "images/payplug.png') no-repeat 3px 2px";
          
          editor.addButton( 'payplug_shortcode_button', {
               style: buttonstyle,
               tooltip: 'Shortcode PayPlug',
               onclick: function() {editor.insertContent('[payplug price="Your_price" title_button="Your_title"]');}
          } );          
     } );
} )();