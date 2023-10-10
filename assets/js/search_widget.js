function widgetLoadImage(id, url)
{
    if($('#main-ajax-container').length)
    {
        showFile(id);
        return false;
    }

    window.location = url;
}

function widgetLoadAlbum(id, url)
{
    if($('#main-ajax-container').length)
    {
        loadImages('folder', id);
        return false;
    }

    window.location = url;
}

var engine1 = new Bloodhound({
	datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title', 'thumbnail', 'id'),
	queryTokenizer: Bloodhound.tokenizers.obj.whitespace('title', 'thumbnail', 'id'),
	identify: "id",
	remote: {
		url: ACCOUNT_WEB_ROOT+'/ajax/search_widget?type=images&query=%QUERY',
		wildcard: '%QUERY',
		rateLimitWait: 100
	}
});

var engine2 = new Bloodhound({
	datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title', 'thumbnail', 'id'),
	queryTokenizer: Bloodhound.tokenizers.obj.whitespace('title', 'thumbnail', 'id'),
	identify: "id",
	remote: {
		url: ACCOUNT_WEB_ROOT+'/ajax/search_widget?type=folders&query=%QUERY',
		wildcard: '%QUERY',
		rateLimitWait: 100
	}
});
 
$('#top-search .typeahead').typeahead({
		minLength: 3, // send AJAX request only after user type in at least 3 characters
		highlight: true,
		cache: false,
		hint: false
	},
	{
		name: 'search-result',
		source: engine1,
		display: 'none',
		limit: 8,
		valueKey: 'id',
		templates: {
			header: function(obj) { return '<h3 class="group-title">Files</h3>'; },
			notFound: '<h3 class="group-title">Files</h3><div class="search-widget-no-results">No files found in search.</div>',
			suggestion: Handlebars.compile('<div><a href="{{url}}" onClick=\'widgetLoadImage({{id}}, "{{url}}"); return false;\'><img src="{{thumbnail}}" class="listing-image-thumbnail"/> <span class="listing-image-title">{{title}}</span><span class="listing-image-sub-title">{{folder_name}}</span><div class="clear"></div></a></div>')
		}
	},
	{
		name: 'search-result2',
		source: engine2,
		display: 'none',
		limit: 8,
		valueKey: 'id',
		templates: {
			header: function(obj) { return '<h3 class="group-title">Folders</h3>'; },
			notFound: '<h3 class="group-title">Folders</h3><div class="search-widget-no-results">No folders found in search.</div>',
			suggestion: Handlebars.compile('<div><a href="{{url}}" onClick=\'widgetLoadAlbum({{id}}, "{{url}}"); return false;\'><img src="{{thumbnail}}" class="listing-image-thumbnail"/> <span class="listing-image-title">{{title}}</span><span class="listing-image-sub-title">{{total_files}}</span><div class="clear"></div></a></div>')
		}
}).on('typeahead:asyncrequest', function() {
	//$('.Typeahead-spinner').show();
})
.on('typeahead:asynccancel typeahead:asyncreceive', function() {
	//$('.Typeahead-spinner').hide();
});