(function($){

	var config = {
		getKey: function(file) {
			return file.name;
		}
	};

	window.acfs3 = window.acfs3 || config;

	function updateTemplate($el, template, files) {
		return $el.html(template({ files: files }));
	}
	
	function initialize_field( $el ) {

		var $templateEl = $el.find('.acf-s3-files');
		var template = _.template($('#acf-s3-file-template').text());
		var files = window.ACF_S3_FILES;

		if ( !$.isArray(files) ) {
			files = [];
		}

		updateTemplate($templateEl, template, files);

		var proxy = new S3Proxy(ajaxurl + '?action=acf-s3_content_action');
		var uploader = new S3FileUploader(proxy);

		proxy.listMultipartUploads().done(function(result) {
			console.log(result);

			if ( !result.Uploads ) {
				return;
			}

			result.Uploads.forEach(function(u) {
				proxy.abortMultipartUpload(u.Key, u.UploadId);
			});
		});

		function logFunc(arg) {
			console.log('Success - Key: ' + arg.Key + '; Part: ' + arg.PartNumber + '; ETag: ' + arg.ETag);
		}

		var queue = [];

		$el.on('change', 'input[type=file]', function(event) {
			var $this = $(event.target);
			var file = $this[0].files[0];
			queue.push(file);

			files.push({
				name: config.getKey(file),
				uploaded: false
			});

			updateTemplate($templateEl, template, files);
		});

		console.log(_);


	}
	
	
	if( typeof acf.add_action !== 'undefined' ) {
	
		/*
		*  ready append (ACF5)
		*
		*  These are 2 events which are fired during the page load
		*  ready = on page load similar to $(document).ready()
		*  append = on new DOM elements appended via repeater field
		*
		*  @type	event
		*  @date	20/07/13
		*
		*  @param	$el (jQuery selection) the jQuery element which contains the ACF fields
		*  @return	n/a
		*/
		
		acf.add_action('ready append', function( $el ){
			
			// search $el for fields of type 'FIELD_NAME'
			acf.get_fields({ type : 's3_content'}, $el).each(function(){
				
				initialize_field( $(this) );
				
			});
			
		});
		
		
	} else {
		
		
		/*
		*  acf/setup_fields (ACF4)
		*
		*  This event is triggered when ACF adds any new elements to the DOM. 
		*
		*  @type	function
		*  @since	1.0.0
		*  @date	01/01/12
		*
		*  @param	event		e: an event object. This can be ignored
		*  @param	Element		postbox: An element which contains the new HTML
		*
		*  @return	n/a
		*/
		
		$(document).on('acf/setup_fields', function(e, postbox){
			
			$(postbox).find('.field[data-field_type="s3_content"]').each(function(){
				
				initialize_field( $(this) );
				
			});
		
		});
	
	
	}


})(jQuery);
