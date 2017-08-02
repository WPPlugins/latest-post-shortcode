(function ($) {
    window.LPS_check_ajax_pagination = {
        config: {},
        init: function () {
            LPS_check_ajax_pagination.initEvents();
        },
        initEvents: function () {
            LPS_check_ajax_pagination.sectionsSetup();
        },
        sectionsSetup: function () {
            $('ul.pages.latest-post-selection').each(function () {
                var $parent = $(this).parent();
                var $maybe_ajax = $parent.find('.ajax_pagination'); 
                if (typeof $maybe_ajax === 'object' && $maybe_ajax.length) {
                    $(this).find('li>a').on('click', function (e) {
                        e.preventDefault();
                        LPS_check_ajax_pagination.lpsNavigate(
                            $parent,
                            $(this).data('page'),
                            $parent.data('args')
                        );
                    });
                }
            });
        },
        lpsNavigate: function ($parent, page, args) {
            $.ajax({
                type: "POST",
                url: LPS.ajaxurl,
                data: {
                    action: 'lps_navigate_to_page',
                    page: page,
                    args: args,
                    lps_ajax: 1,
                },
                cache: false,
            }).success(function (response) {
                $parent.html(response);
                LPS_check_ajax_pagination.init();
            });
        }
    };

    $(document).ready(function () {
        LPS_check_ajax_pagination.init();
    });

})(jQuery);
