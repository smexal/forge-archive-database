var archiveDatabase = {
    steps : 0,
    grid : false,
    msnry : false,

    init : function(context) {
        var context = $(document);
        context.find(".add-select-button").unbind('click').on('click', function() {
            archiveDatabase.clickAddSelect(context, $(this));
        });
        context.find(".remove-before").unbind('click').on('click', function() {
            archiveDatabase.clickRemoveSelect(context, $(this));
        });

        archiveDatabase.masonry();
        archiveDatabase.searchField();
        archiveDatabase.inifiniteLoading(context);

        var searchField = $("input[name='adb_search']");
        var searchVal = decodeURI(helpers.getCookie("adb_search_value"));
        if(searchField.length > 0 && searchVal !== searchField.val()) {
            searchField.val(searchVal);
            searchField.closest('.form-group').addClass('focus');
            searchField.trigger('input');
        }

        archiveDatabase.bigClick();
        archiveDatabase.toggleAdvanced();
    },

    toggleAdvanced : function() {
        $(".adb-toggle-advanced").unbind('click').on('click', function() {
            if($(".adb-advanced-search").hasClass('show')) {
                $(".adb-advanced-search").removeClass('show');
            } else {
                $(".adb-advanced-search").addClass('show');
            }
        });

        $(document).find("input[name^='adb_s_']").unbind('input').on('input', function () {
            $(document).find("input[name='adb_search']").trigger('input');
        });
    },

    bigClick : function() {
        var idArrayForZapping = new Array();
        $("#archive-database img.adb-thumb").each(function() {
            idArrayForZapping.push($(this).attr('data-id'));
        });
        $("#archive-database img.adb-thumb").each(function() {
            $(this).on('click', function() {
                var requestUrl = $("#archive-database").attr('data-base-url');
                requestUrl += '/api/archive-database/detailImage/' + $(this).attr('data-id');
                rdon_overlay.open();
                rdon_overlay.load(requestUrl);
            })
        });
    },

    isMasonry : function() {
        var table = $("table[id^='infinite___'],div[id^='infinite___']");
        if(table.length == 0 || table.find("tbody").length > 0) {
            return;
        }
        return true;
    },

    masonry : function() {
        var table = $("table[id^='infinite___'],div[id^='infinite___']");
        if(table.length == 0 || table.find("tbody").length > 0) {
            return;
        }

        $(".grid").imagesLoaded().progress( function() {
            archiveDatabase.msnry = $(".grid").masonry({
              itemSelector: '.grid-element'
            });
        });
    },

    searchField: function () {
        var timeout = false;
        $(document).find("input[name='adb_search']").unbind('input').on('input', function () {
            clearTimeout(timeout);
            var input = $(this);

            timeout = setTimeout(function() {
                var table = $(document).find("table[id^='infinite___'],div[id^='infinite___']");
                var type = table.attr('id').split('___');
                type = type[1];
                var requestUrl = $("#archive-database").attr('data-base-url') + '/api/archive-database/infiniteLoading/'+type+
                    '?limit=30&offset=0'+
                    '&search=' + encodeURI(input.val())+
                    '&s_title=' + encodeURI($("input[name='adb_s_title']").val()) +
                    '&s_creation_date=' + encodeURI($("input[name='adb_s_creation_date']").val()) +
                    '&s_subject=' + encodeURI($("input[name='adb_s_subject']").val()) +
                    '&s_identifier=' + encodeURI($("input[name='adb_s_identifier']").val()) +
                    '&grid='+archiveDatabase.grid;

                if(archiveDatabase.isMasonry()) {
                    archiveDatabase.msnry.masonry('destroy');
                    archiveDatabase.msnry.html('');
                }
                var loader = $('<div class="sk-folding-cube"><div class="sk-cube1 sk-cube"></div><div class="sk-cube2 sk-cube"></div><div class="sk-cube4 sk-cube"></div><div class="sk-cube3 sk-cube"></div></div>');
                $(".grid").css({height : '100px'});
                $(".grid").append(loader);

                $.ajax({
                    method: 'GET',
                    url: requestUrl
                }).done(function(data) {
                    helpers.setCookie("adb_search_value", encodeURI(input.val()), 1);
                    if(table.find("tbody").length > 0) {
                        table.find("tbody").html(data.newRows);
                    } else {
                        var $newtems = $(data.newRows);
                        // layout Masonry after each image loads
                        $(".grid").html($newtems);
                        setTimeout(function() {
                            $newtems.imagesLoaded().progress( function() {
                                archiveDatabase.msnry = $(".grid").masonry({
                                    itemSelector: '.grid-element'
                                });
                            });
                        }, 500);
                    }
                    loader.fadeOut(400, function() {
                        table.find(".spinner").remove();
                    });
                    $(document).trigger("ajaxReload");
                });
            }, 600);
        });
    },

    inifiniteLoading : function(context) {
        var table = context.find("table[id^='infinite___'],div[id^='infinite___']");
        if(table.length == 0) {
            return;
        }
        if(archiveDatabase.steps === 0) {
            archiveDatabase.steps = table.find("tbody>tr").length
            if(archiveDatabase.steps == 0) {
                archiveDatabase.grid = true;
                archiveDatabase.steps = table.find("> div").length;
            }
        }
        var type = table.attr('id').split('___');
        type = type[1];
        $(window).unbind('scroll').scroll(function() {
            if(Math.ceil($(window).scrollTop() + $(window).height()) == Math.ceil($(document).height())) {
                // get search query value
                var searchVal = $("input[name='adb_search']").val();
                if(typeof(searchVal) === 'undefined') {
                    searchVal = '';
                }

                var rows = table.find("tbody>tr");
                if(rows.length == 0) {
                    rows = table.find(">div");
                }
                var requestUrl = $("#archive-database").attr('data-base-url') + '/api/archive-database/infiniteLoading/'+type+
                    '?limit='+archiveDatabase.steps+
                    '&offset='+rows.length+
                    '&search='+searchVal+
                    '&grid='+archiveDatabase.grid;

                var loader = $('<div class="sk-folding-cube"><div class="sk-cube1 sk-cube"></div><div class="sk-cube2 sk-cube"></div><div class="sk-cube4 sk-cube"></div><div class="sk-cube3 sk-cube"></div></div>');
                if(archiveDatabase.grid) {
                    table.append(loader);
                } else {
                    // add loadter to tfoot..
                }

                $.ajax({
                    method: 'GET',
                    url: requestUrl
                }).done(function(data) {
                    if(data.newRows == '') {
                        $(window).unbind('scroll');
                    } else {
                        if(table.find("tbody").length > 0) {
                            table.find("tbody").append(data.newRows);
                        } else {
                            var $newtems = $(data.newRows);
                            archiveDatabase.msnry.append($newtems).masonry( 'appended', $newtems );
                            // layout Masonry after each image loads
                            $newtems.imagesLoaded().progress( function() {
                                archiveDatabase.msnry.masonry('layout');
                            });
                        }
                        $(document).trigger("ajaxReload");
                    }
                    
                    loader.fadeOut(400, function() {
                        table.find(".spinner").remove();
                    });
                });
            }
        });
    },

    clickRemoveSelect : function(context, trigger) {
        trigger.closest(".add-group").remove();
    },

    clickAddSelect : function(context, trigger) {
        var base = trigger.data('base');
        var type = trigger.data('type');
        var index = trigger.data('index');
        trigger.data('index', index+1);
        var selectNameId = type+'___'+index;
        var nothingText = trigger.data('nothing');
        var requestUrl = $("#archive-database").attr('data-base-url') + '/api/archive-database/getSelect/'+
                    '?type='+type+
                    '&count='+index;
        $.ajax({
          method: "GET",
          url: requestUrl
        }).done(function( data ) {
            var select = '<div class="add-group">' + data.select + '</div>';
            /*<div class="form-group select">';
            select+= data.select;
            
            select+= '<select class="form-control chosen-select" name="'+selectNameId+'" id="'+selectNameId+'">';
            select+= '<option value="0">'+nothingText+'</option>';
            $.each(data, function(index, value) {
                var val = value.name;
                if(type == 'people') {
                    val+= ", " +value.forename;
                }
                select+= '<option value="'+value.id+'">'+val+'</option>';
            })
            select+= '</select>';
            select+= '</div>';
            select+= '<a href="javascript://" class="remove-before"><i class="material-icons">remove_circle_outline</i></a>';
            select+= '</div>';
            */

            var insertAfter = trigger.closest('.form-grouping').find(".add-group").filter(":last")
            if(insertAfter.length == 0) {
                insertAfter = trigger.closest('.form-grouping').find("h4").filter(":last");
            }
            var s = $(select);
            s.insertAfter(insertAfter);
            archiveDatabase.init();
            $(document).trigger("ajaxReload");
        });
    }
}

$(document).ready(archiveDatabase.init);
$(document).on("ajaxReload", function(evt, context) {
    archiveDatabase.init();
});
