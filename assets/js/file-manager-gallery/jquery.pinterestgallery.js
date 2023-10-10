/* 
	Author: http://codecanyon.net/user/sike
*/      
;(function($){      
	
	var _currentLargeNum;
	function clickCard(event){
		var _item = $(event.data.target);
		var _this = event.data.root;
		var settings = _this.data('settings');
		var _url = _item.data('large');
		var _container = $('div#' + settings.largeContainerID);
		var _assetCon = _container.find('div.assetContainer');		
		_currentLargeNum = $.inArray(_url, _this.data('largeArr'));
		if(_assetCon[0]==null||_assetCon[0]==undefined){
			_assetCon = $('<div class="assetContainer"></div>').css('position', 'relative');			
			_container.append(_assetCon);					

			_container.on('click', function(event) {
				if($(event.target).is('div#' + settings.largeContainerID)||$(event.target).is(_assetCon)){
					$(this).hide();
					_container.find('img').remove();
					_container.find('span').remove();
					_container.find('iframe').remove();										
				}
			});
	                               
			$(window).resize(function(event){	 					
				var _aw = _assetCon.find('img').outerWidth() || _assetCon.find('iframe').outerWidth();
				var _ah = _assetCon.find('img').outerHeight() || _assetCon.find('iframe').outerHeight();
				_assetCon.css({
					'marginTop': ($(window).height() - _ah)*.5 + 'px',
					'marginLeft': ($(window).width() - _aw)*.5 + 'px'										
				});					
								
			});
		}
		
		event.stopImmediatePropagation();
	}
		
	function overCard(event){
		var _item = $(event.data.target);
		var settings = event.data.root.data('settings');
		var _frontContainer = _item.find('.'+settings.frontContainer);
		var _backContainer = _item.find('.'+settings.backContainer);
		
		_item.stop(true, true);	
        _frontContainer.css('z-index', 800).css('opacity', '0');
		_backContainer.css('z-index', 900).css('opacity', '1');        
		event.stopImmediatePropagation();
	}
			
	function outCard(event){
		var _item = $(event.data.target);
		var settings = event.data.root.data('settings');			
		var _frontContainer, _backContainer;
		_item.stop(true, true).animate({opacity: 1}, settings.animateDelay, function() {

			
		})
		event.stopImmediatePropagation();
        
        _frontContainer = _item.find('.'+settings.frontContainer);
		_backContainer = _item.find('.'+settings.backContainer);
		_frontContainer.css('z-index', 900).css('opacity', '1');
		_backContainer.css('z-index', 800).css('opacity', '0');
                                
	}
				
	$.fn.extend({
		pinterestGallery: function(options) {
	      	// plugin default options, it's extendable
			var settings = { 
				largeContainerID:'theLarge',
				gridOptions: {
			        autoResize: true, // This will auto-update the layout when the browser window is resized.
			        container: $('#main'), // Optional, used for some extra CSS styling
			        offset: 2, // Optional, the distance between grid items
                    flexibleWidth: function () {
                        // return a maximum width depending on the viewport
                        return (Math.max(document.documentElement.clientWidth, window.innerWidth || 0)) < 1024 ? '100%' : '50%';
                    },
                    align: "left"	 // Optional, the width of a grid item					
				},		
				frontContainer: 'front', 
				backContainer: 'back',
				animateStyle: 'twirl',
				animateDelay: '0'	
			}; 
			
  			// extends settings with options provided
	        if (options) {
				$.extend(settings, options);
			} 

			var _this = this; 			
			_this.data('settings', settings);

		    // Get a reference to your grid items.
	    	var handler = _this.find('.fileIconLi');
			handler.wookmark(settings.gridOptions);    
            //wookmark = new Wookmark('#fileListing', settings.gridOptions);	
			var _container = $('div#' + settings.largeContainerID);					
			_container.css('opacity', 0).hide();
			
			var _frontContainer, _backContainer;
			var _largeArr = [], _itemArr = [];
			var _largeIndex = 0;
			_this.find('.fileIconLi').each(function(index) {
				_frontContainer = $(this).find('.'+settings.frontContainer);
				_backContainer = $(this).find('.'+settings.backContainer);

				_backContainer.css({
					'z-index': 800,
					'opacity': 0
				});
				_frontContainer.css('z-index', 900);
								
			  	$(this).on('mouseover', {target:$(this), root:_this}, overCard);
				$(this).on('mouseleave', {target:$(this), root:_this}, outCard);											
				$(this).find('.back').css('height', $(this).find('.front').outerHeight())				
			});
			_this.data('itemArr', _itemArr);			
			_this.data('largeArr', _largeArr);
								
			return this;
		}

	});
		
})(jQuery);