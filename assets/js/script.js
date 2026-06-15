jQuery(document).ready(function ($) {
    var containers = $('.fcb-container');

    if (containers.length === 0) return;

    containers.each(function () {
        var container = $(this);
        var mainButton = container.find('.fcb-main-button');
        var linksContainer = container.find('.fcb-links-container');

        // Initially hide the close icon by hiding it visually but tracking it
        mainButton.find('.fa-times').hide();

        mainButton.on('click', function (e) {
            e.preventDefault();

            mainButton.toggleClass('fcb-open');
            linksContainer.toggleClass('fcb-active');

            // Handle staggered animation delays
            if (linksContainer.hasClass('fcb-active')) {
                var itemsOptions = linksContainer.find('.fcb-link-item').toArray().reverse();
                $(itemsOptions).each(function (index, el) {
                    $(el).css('animation-delay', (index * 0.05) + 's');
                });

                // Switch icons
                mainButton.find('i:not(.fa-times)').fadeOut(150, function () {
                    mainButton.find('.fa-times').fadeIn(150);
                });
            } else {
                linksContainer.find('.fcb-link-item').css('animation-delay', '0s');

                // Switch icons
                mainButton.find('.fa-times').fadeOut(150, function () {
                    mainButton.find('i:not(.fa-times)').fadeIn(150);
                });
            }
        });
    });

    // Close when clicking outside of any button container
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.fcb-container').length) {
            $('.fcb-container').each(function () {
                var container = $(this);
                var mainButton = container.find('.fcb-main-button');
                var linksContainer = container.find('.fcb-links-container');

                if (linksContainer.hasClass('fcb-active')) {
                    mainButton.removeClass('fcb-open');
                    linksContainer.removeClass('fcb-active');

                    mainButton.find('.fa-times').fadeOut(150, function () {
                        mainButton.find('i:not(.fa-times)').fadeIn(150);
                    });
                }
            });
        }
    });

    // --- Popup Functionality ---

    // Open popup when clicking a popup-type link item
    $(document).on('click', '[data-fcb-popup], a[href*="#fcb-popup-"]', function (e) {
        var href = $(this).attr('href');
        // If it's a link and doesn't contain #fcb-popup-, let it be
        if (!$(this).data('fcb-popup') && (!href || href.indexOf('#fcb-popup-') === -1)) {
            return;
        }

        e.preventDefault();
        e.stopImmediatePropagation();

        var popupId = $(this).data('fcb-popup');
        if (!popupId && href) {
            popupId = href.split('#')[1];
        }

        var $overlay = $('#' + popupId);
        if ($overlay.length) {
            $overlay.css('display', 'flex');
            // Trigger reflow for animation
            $overlay[0].offsetHeight;
            $overlay.addClass('fcb-popup-visible');
            $('body').css('overflow', 'hidden');
        }
    });

    // Automatically open popup if URL has the hash
    if (window.location.hash && window.location.hash.indexOf('#fcb-popup-') === 0) {
        var hashId = window.location.hash.substring(1);
        var $overlayOnLoad = $('#' + hashId);
        if ($overlayOnLoad.length) {
            setTimeout(function() {
                $overlayOnLoad.css('display', 'flex');
                $overlayOnLoad[0].offsetHeight;
                $overlayOnLoad.addClass('fcb-popup-visible');
                $('body').css('overflow', 'hidden');
            }, 100);
        }
    }

    // Close popup on close button click
    $(document).on('click', '.fcb-popup-close', function (e) {
        e.preventDefault();
        fcbClosePopup($(this).closest('.fcb-popup-overlay'));
    });

    // Close popup on overlay backdrop click
    $(document).on('click', '.fcb-popup-overlay', function (e) {
        if ($(e.target).hasClass('fcb-popup-overlay')) {
            fcbClosePopup($(this));
        }
    });

    // Close popup on Escape key
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            var $visible = $('.fcb-popup-overlay.fcb-popup-visible');
            if ($visible.length) {
                fcbClosePopup($visible);
            }
        }
    });

    function fcbClosePopup($overlay) {
        $overlay.removeClass('fcb-popup-visible');
        setTimeout(function () {
            $overlay.css('display', 'none');
            $('body').css('overflow', '');
        }, 300); // Match CSS transition duration
    }
});
