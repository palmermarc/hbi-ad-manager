(function($) {

	DFPContentGrid = function(settings)
	{
		this.settings       = settings;
		this.nodeClass      = '.fl-node-' + settings.id;
		this.wrapperClass   = this.nodeClass + ' .pp-content-post-' + this.settings.layout;
		this.postClass      = this.wrapperClass + ' .pp-content-' + this.settings.layout + '-post';
		this.matchHeight	= settings.matchHeight == 'yes' ? true : false;
		this.masonry		= settings.masonry == 'yes' ? true : false;
		this.perPage 		= settings.perPage;
		this.filters 		= settings.filters === 'yes' ? true : false;
		this.filterTax 		= settings.filterTax;
		this.filterType 	= settings.filterType;

		if(this._hasPosts()) {
			this._initLayout();
			this._initInfiniteScroll();
		}
	};

	DFPContentGrid.prototype = {

		settings        : {},
		nodeClass       : '',
		wrapperClass    : '',
		postClass       : '',
		gallery         : null,
		perPage			: '',
		filters			: false,
		filterTax		: '',
		filterType		: '',
		filterData		: {},
		activeFilter	: '',
		cacheData		: {},
		'infscr' 		: '',

		_hasPosts: function()
		{
			return $(this.postClass).length > 0;
		},

		_initLayout: function()
		{
			switch(this.settings.layout) {

				case 'grid':
				this._gridLayout();
				break;

				case 'carousel':
				this._carouselLayout();
				break;

			}

			$(this.postClass).css('visibility', 'visible');
		},

		_gridLayout: function()
		{
			var wrap = $(this.wrapperClass);

			var postFilterData = {
				itemSelector: '.pp-content-post',
				percentPosition: true,
				transitionDuration: '0.4s',
			};

			if ( !this.masonry ) {
				postFilterData = $.extend( {}, postFilterData, {
					layoutMode: 'fitRows',
					fitRows: {
						gutter: '.pp-grid-space'
				  	},
				} );
			}

			if ( this.masonry ) {

				postFilterData = $.extend( {}, postFilterData, {
					masonry: {
						columnWidth: '.pp-content-post',
						gutter: '.pp-grid-space'
					},
				} );
			}

			// set filter data globally to use later for ajax scroll pagination.
			this.filterData = postFilterData;

			wrap.imagesLoaded( $.proxy( function() {

				var node = $(this.nodeClass);
                var base = this;


                if ( this.settings.filters || this.masonry ) {

					var postFilters = $(this.nodeClass).find('.pp-content-post-grid').isotope(postFilterData);
					var filterWrap = $(this.nodeClass).find('.pp-post-filters');
					var filterToggle = $(this.nodeClass).find('.pp-post-filters-toggle');

					filterToggle.on('click', function () {
						filterWrap.slideToggle(function () {
							if ($(this).is(':visible')) {
								$(this).addClass('pp-post-filters-open');
							}
							if (!$(this).is(':visible')) {
								$(this).removeClass('pp-post-filters-open');
							}
						});
					});

					filterWrap.on('click', '.pp-post-filter', function() {
						// set active filter globally to use later for ajax scroll pagination.
						base.activeFilter = $(this).data('term');

						if ('static' === base.filterType) {
							var filterVal = $(this).attr('data-filter');
							postFilters.isotope({ filter: filterVal });
						} else {
							var term = $(this).data('term');
							base._getPosts(term, postFilterData);
						}

						filterWrap.find('.pp-post-filter').removeClass('pp-filter-active');
						$(this).addClass('pp-filter-active');

						// if (!base.masonry) {
						// 	if (filterVal !== '*' && 'static' === base.filterType) {
						// 		base._gridLayoutMatchHeightSimple();
						// 	} else {
						// 		base._gridLayoutMatchHeight();
						// 	}
						// 	node.find('.pp-content-post-grid').isotope('layout');
						// }
						
						filterToggle.find('span.toggle-text').html($(this).text());
						if (filterWrap.hasClass('pp-post-filters-open')) {
							filterWrap.slideUp();
						}
					});
					
					if ('dynamic' === base.filterType) {
						$(base.nodeClass).find('.fl-builder-pagination a').off('click').on('click', function (e) {
							e.preventDefault();
							var pageNumber = base._getPageNumber( this );
							base._getPosts('', postFilterData, pageNumber);
						});
					}

					// Trigger filter by hash parameter in URL.
					if ( '' !== location.hash ) {
						var filterHash = location.hash.split('#')[1];

						filterWrap.find('li[data-term="' + filterHash + '"]').trigger('click');
					}

					// Trigger filter on hash change in URL.
					$(window).on('hashchange', function() {
						if ( '' !== location.hash ) {
							var filterHash = location.hash.split('#')[1];
	
							filterWrap.find('li[data-term="' + filterHash + '"]').trigger('click');
						}
					});
                }

                if( !this.masonry ) {
                    setTimeout( function() {
                        base._gridLayoutMatchHeight();
                    }, 1000 );
                }

                if ( this.settings.filters || this.masonry ) {
                    setTimeout( function() {
						if ('static' === base.filterType) {
							node.find('.pp-filter-active').trigger('click');
						}
						if ( !base.masonry ) {
							base._gridLayoutMatchHeight();
						}
						node.find('.pp-content-post-grid').isotope('layout');

                    }, 1000 );
                }

			}, this ) );
		},

		_carouselLayout: function()
		{
			var wrap = $(this.nodeClass + ' .pp-content-post-carousel .pp-content-posts-inner');
			wrap.imagesLoaded( $.proxy( function() {
				var owlOptions = {
					afterUpdate: $.proxy(this._gridLayoutMatchHeightSimple, this),
					afterInit: $.proxy(this._gridLayoutMatchHeightSimple, this),
					afterLazyLoad: $.proxy(this._gridLayoutMatchHeightSimple, this),
				};
				//console.log($.extend({}, this.settings.carousel, owlOptions));
				wrap.owlCarousel( $.extend({}, this.settings.carousel, owlOptions) );
			}, this));

		},

		_getPosts: function (term, isotopeData, paged) {
			var processAjax = false,
				filter 		= term,
				paged 		= (!paged || 'undefined' === typeof paged) ? 1 : paged;

			if ('undefined' === typeof term || '' === filter) {
				filter = 'all';
			}

			var cacheData = this._getCacheData(filter);

			if ('undefined' === typeof cacheData) {
				processAjax = true;
			} else {
				var cachedResponse = cacheData.page[paged];
				if ('undefined' === typeof cachedResponse) {
					processAjax = true;
				} else {
					this._renderPosts(cachedResponse, {
						term: term,
						isotopeData: isotopeData,
						page: paged
					});
				}
			}

			if (processAjax) {
				this._getAjaxPosts(term, isotopeData, paged);
			}
		},

		_getAjaxPosts: function (term, isotopeData, paged) {
			var taxonomy = this.filterTax,
				perPage = this.perPage,
				paged = 'undefined' === typeof paged ? false : paged,
				self = this;

			var gridWrap = $(this.wrapperClass);

			var currentPage = this.settings.current_page.split('?')[0];

			var data = {
				action: 'pp_grid_get_posts',
				node_id: this.settings.id,
				page: !paged ? this.settings.page : paged,
				current_page: currentPage,
				settings: this.settings.fields
			};

			if ('' !== term || 'undefined' === typeof term) {
				data['term'] = term;
			}
			if ('undefined' !== self.settings.orderby || '' !== self.settings.orderby) {
				data['orderby'] = self.settings.orderby;
			}

			gridWrap.addClass('pp-is-filtering');

			$.ajax({
				type: 'post',
				url: self.settings.ajaxUrl,
				data: data,
				success: function (response) {
					gridWrap.removeClass('pp-is-filtering');
					self._setCacheData(term, response, paged);
					self._renderPosts(response, {
						term: term,
						isotopeData: isotopeData,
						page: paged
					});
				}
			});
		},

		_renderPosts: function (response, args) {
			var self = this,
				wrap = $(this.wrapperClass);

			wrap.isotope('remove', $(this.postClass));

			if (!this.masonry) {
				wrap.isotope('insert', $(response.data), $.proxy(this._gridLayoutMatchHeight, this));
				wrap.imagesLoaded($.proxy(function () {
					setTimeout(function () {
						self._gridLayoutMatchHeight();
					}, 150);
				}, this));
			} else {
				wrap.isotope('insert', $(response.data));
			}
			
			wrap.find('.pp-grid-space').remove();
			wrap.append('<div class="pp-grid-space"></div>');

			wrap.imagesLoaded($.proxy(function () {
				setTimeout(function () {
					wrap.isotope('layout');
				}, 500);
			}, this));

			if (response.pagination) {
				$(self.nodeClass).find('.fl-builder-pagination').remove();
				$(self.nodeClass).find('.fl-module-content').append(response.pagination);
				$(self.nodeClass).find('.pp-ajax-pagination a').off('click').on('click', function (e) {
					e.preventDefault();
					var pageNumber = self._getPageNumber( this );
					self._getPosts(args.term, args.isotopeData, pageNumber);
				});
			} else {
				$(self.nodeClass).find('.fl-builder-pagination').remove();
			}

			var offsetTop = wrap.offset().top - 200;
			$('html, body').stop().animate({
				scrollTop: offsetTop
			}, 300);

			// re-initialize infinitescroll.
			wrap.imagesLoaded($.proxy(function () {
				setTimeout(function () {
					if(self.settings.pagination == 'scroll' && typeof FLBuilder === 'undefined') {
						self._destroyInfiniteScroll();
						wrap.isotope( self.filterData );
						self._infiniteScroll();
					}
				}, 1500);
			}, this));
		},

		_getPageNumber: function( pageElement )
		{
			var pageNumber = parseInt( $(pageElement).text() ); //$(pageElement).attr('href').split('#page-')[1];

			if ( $(pageElement).hasClass('next') ) {
				pageNumber = parseInt( $(pageElement).parents('.pp-content-grid-pagination').find('.current').text() ) + 1;
			}
			if ( $(pageElement).hasClass('previous') ) {
				pageNumber = parseInt( $(pageElement).parents('.pp-content-grid-pagination').find('.current').text() ) - 1;
			}

			return pageNumber;
		},

		_setCacheData: function (filter, response, paged) {
			if ('undefined' === typeof filter || '' === filter) {
				filter = 'all';
			}
			if ('undefined' === typeof paged || !paged) {
				paged = 1;
			}

			if ('undefined' === typeof this.cacheData.ajaxCache) {
				this.cacheData.ajaxCache = {};
			}
			if ('undefined' === typeof this.cacheData.ajaxCache[filter]) {
				this.cacheData.ajaxCache[filter] = {};
			}
			if ('undefined' === typeof this.cacheData.ajaxCache[filter].page) {
				this.cacheData.ajaxCache[filter].page = {};
			}

			return this.cacheData.ajaxCache[filter].page[paged] = response;
		},

		_getCacheData: function (filter) {
			var cacheData = this.cacheData;

			if ('undefined' === typeof cacheData.ajaxCache) {
				cacheData.ajaxCache = {};
			}
			//console.log(cacheData);

			return cacheData.ajaxCache[filter];
		},

		_gridLayoutMatchHeight: function()
		{
			var highestBox = 0;
			var contentHeight = 0;
			var postElements = $(this.postClass + ':visible');
			var columns = this.settings.postColumns.desktop;

			if (0 === this.matchHeight || 1 === columns) {
				return;
			}

			postElements.css('height', 'auto');

			if ( this.settings.layout === 'grid' ) {

				if (window.innerWidth <= 768) {
					columns = this.settings.postColumns.tablet;
				}
				if (window.innerWidth <= 600) {
					columns = this.settings.postColumns.mobile;
				}

				var rows = Math.round(postElements.length / columns);

				if ( postElements.length % columns > 0 ) {
					rows = rows + 1;
				}

				// range.
				var j = 1,
					k = columns;

				for( var i = 0; i < rows; i++ ) {
					// select number of posts in the current row.
					var postsInRow = $(this.postClass + ':visible:nth-child(n+' + j + '):nth-child(-n+' + k + ')');

					// get height of the larger post element within the current row.
					postsInRow.css('height', '').each(function () {
						if ($(this).height() > highestBox) {
							highestBox = $(this).height();
							contentHeight = $(this).find('.pp-content-post-data').outerHeight();
						}
					});
					// apply the height to all posts in the current row.
					postsInRow.height(highestBox);

					// increment range.
					j = k + 1;
					k = k + columns;
					if ( k > postElements.length ) {
						k = postElements.length;
					}
				}
			} else {
				// carousel layout.
				postElements.css('height', '').each(function(){

					if($(this).height() > highestBox) {
						highestBox = $(this).height();
						contentHeight = $(this).find('.pp-content-post-data').outerHeight();
					}
				});

				postElements.height(highestBox);
			}
            //$(this.postClass).find('.pp-content-post-data').css('min-height', contentHeight + 'px').addClass('pp-content-relative');
		},

		_gridLayoutMatchHeightSimple: function () {
			var highestBox = 0;
			var contentHeight = 0;
			var postElements = $(this.postClass);

			if (0 === this.matchHeight) {
				return;
			}

			postElements.css('height', '').each(function () {

				if ($(this).height() > highestBox) {
					highestBox = $(this).height();
					contentHeight = $(this).find('.pp-content-post-data').outerHeight();
				}
			});

			postElements.height(highestBox);
		},

		_initInfiniteScroll: function()
		{
			if(this.settings.pagination == 'scroll' && typeof FLBuilder === 'undefined') {
				this._infiniteScroll();
			}
		},

		_infiniteScroll: function()
		{
			var path 		= $(this.nodeClass + ' .pp-content-grid-pagination a.next').attr('href'),
				pagePattern = /(.*?(\/|\&|\?)paged-[0-9]{1,}(\/|=))([0-9]{1,})+(.*)/,
				pageMatched = null,
				scrollData	= {
					navSelector     : this.nodeClass + ' .pp-content-grid-pagination',
					nextSelector    : this.nodeClass + ' .pp-content-grid-pagination a.next',
					itemSelector    : this.postClass,
					prefill         : true,
					bufferPx        : 200,
					loading         : {
						msgText         : 'Loading',
						finishedMsg     : '',
						img             : FLBuilderLayoutConfig.paths.pluginUrl + 'img/ajax-loader-grey.gif',
						speed           : 1
					},
				};

			// Define path since Infinitescroll incremented our custom pagination '/paged-2/2/' to '/paged-3/2/'.
			if ( pagePattern.test( path ) ) {
				scrollData.path = function( currPage ){
					pageMatched = path.match( pagePattern );
					path = pageMatched[1] + currPage + pageMatched[5];
					return path;
				}
			}

			$(this.wrapperClass).infinitescroll( scrollData, $.proxy(this._infiniteScrollComplete, this) );

			setTimeout(function(){
				$(window).trigger('resize');
			}, 100);
		},

		_infiniteScrollComplete: function(elements)
		{
			var wrap = $(this.wrapperClass);
			var self = this;

			elements = $(elements);

			if(this.settings.layout == 'grid') {
				wrap.imagesLoaded( $.proxy( function() {

					var infscr = wrap.find('#infscr-loading').clone();
					wrap.find('div[id="infscr-loading"]').remove();

					if( ! this.masonry ) {
						this._gridLayoutMatchHeight();
						if( this.settings.filters ) {
							wrap.isotope('insert', elements, this._gridLayoutMatchHeight());
						}
					} else {
						wrap.isotope('insert', elements);
					}

					elements.css('visibility', 'visible');
					wrap.find('.pp-grid-space').remove();
					wrap.append('<div class="pp-grid-space"></div>');
					wrap.append( infscr );

					// re-layout masonry
					setTimeout(function () {
						if( ! this.masonry ) {
							self._gridLayoutMatchHeight();
						}
						wrap.isotope('layout');
					}, 500);
				}, this ) );
			}
		},

		_destroyInfiniteScroll: function()
		{
			$(this.wrapperClass).infinitescroll('destroy');
			$(this.wrapperClass).find('div[id="infscr-loading"]').remove();
			$.removeData( $(this.wrapperClass)[0] );
		},
	};

})(jQuery);
