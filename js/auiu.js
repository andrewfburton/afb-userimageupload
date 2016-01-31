jQuery(document).ready(function($) {    var AUIU_Obj = {        init: function () {            $('#auiu_new_post_form, #auiu_edit_post_form').on('submit', this.checkSubmit);            $('.auiu-post-form').on('click', 'a.auiu-del-ft-image', this.removeFeatImg);            //initialize the featured image uploader            this.featImgUploader();            this.ajaxCategory();        },        checkSubmit: function () {            var form = $(this);            form.find('.requiredField').each(function() {                if( $(this).hasClass('invalid') ) {                    $(this).removeClass('invalid');                }            });            var hasError = false;            $(this).find('.requiredField').each(function() {                var el = $(this),                labelText = el.prev('label').text();                if(jQuery.trim(el.val()) == '') {                    el.addClass('invalid');                    hasError = true;                } else if(el.hasClass('email')) {                    var emailReg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;                    if(!emailReg.test($.trim(el.val()))) {                        el.addClass('invalid');                        hasError = true;                    }                } else if(el.hasClass('cat')) {                    if( el.val() == '-1' ) {                        el.addClass('invalid');                        hasError = true;                    }				}	            });			if($('#auiu-checkbox').prop('checked') == false){				$('input#auiu-checkbox').addClass('invalid');				$('.auiu-check-container > span').addClass('invalid');				hasError = true;			}            if( ! hasError ) {                $(this).find('input[type=submit]').attr({                    'value': auiu.postingMsg,                    'disabled': true                });                return true;            }            return false;        },        featImgUploader: function() {            if(typeof plupload === 'undefined') {                return;            }            if(auiu.featEnabled !== '1') {                return;            }            var uploader = new plupload.Uploader(auiu.plupload);            uploader.bind('Init', function(up, params) {                //$('#cpm-upload-filelist').html("<div>Current runtime: " + params.runtime + "</div>");                });            $('#auiu-ft-upload-pickfiles').click(function(e) {                uploader.start();                e.preventDefault();            });            uploader.init();            uploader.bind('FilesAdded', function(up, files) {                $.each(files, function(i, file) {                    $('#auiu-ft-upload-filelist').append(                        '<div id="' + file.id + '">' +                        file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +                        '</div>');                });                up.refresh(); // Reposition Flash/Silverlight                uploader.start();            });            uploader.bind('UploadProgress', function(up, file) {                $('#' + file.id + " b").html(file.percent + "%");            });            uploader.bind('Error', function(up, err) {                $('#auiu-ft-upload-filelist').append("<div>Error: " + err.code +                    ", Message: " + err.message +                    (err.file ? ", File: " + err.file.name : "") +                    "</div>"                    );                up.refresh(); // Reposition Flash/Silverlight            });            uploader.bind('FileUploaded', function(up, file, response) {                var resp = $.parseJSON(response.response);                //$('#' + file.id + " b").html("100%");                $('#' + file.id).remove();                //console.log(resp);                if( resp.success ) {                    $('#auiu-ft-upload-filelist').append(resp.html);                    $('#auiu-ft-upload-pickfiles').hide();					$("#auiu-ft-upload-container").css('border','none');					$('.auiu-dropfile-text').hide();                }            });        },        removeFeatImg: function(e) {            e.preventDefault();            if(confirm(auiu.confirmMsg)) {                var el = $(this),                    data = {                        'attach_id' : el.data('id'),                        'nonce' : auiu.nonce,                        'action' : 'auiu_feat_img_del'                    }                $.post(auiu.ajaxurl, data, function(){                    $('#auiu-ft-upload-pickfiles').show();					$("#auiu-ft-upload-container").css('border','1px dashed #ccc');					$('.auiu-dropfile-text').show();                    el.parent().remove();                });            }        },        ajaxCategory: function () {            var el = '#cat-ajax',                wrap = '.category-wrap';            $(el).parent().attr('level', 0);            if ($( wrap + ' ' + el ).val() > 0) {                WPUF_Obj.getChildCats( $(el), 'lvl', 1, wrap, 'category');            }            $(wrap).on('change', el, function(){                currentLevel = parseInt( $(this).parent().attr('level') );                WPUF_Obj.getChildCats( $(this), 'lvl', currentLevel+1, wrap, 'category');            });        },        getChildCats: function (dropdown, result_div, level, wrap_div, taxonomy) {            cat = $(dropdown).val();            results_div = result_div + level;            taxonomy = typeof taxonomy !== 'undefined' ? taxonomy : 'category';            $.ajax({                type: 'post',                url: auiu.ajaxurl,                data: {                    action: 'auiu_get_child_cats',                    catID: cat,                    nonce: auiu.nonce                },                beforeSend: function() {                    $(dropdown).parent().parent().next('.loading').addClass('auiu-loading');                },                complete: function() {                    $(dropdown).parent().parent().next('.loading').removeClass('auiu-loading');                },                success: function(html) {                    $(dropdown).parent().nextAll().each(function(){                        $(this).remove();                    });                    if(html != "") {                        $(dropdown).parent().addClass('hasChild').parent().append('<div id="'+result_div+level+'" level="'+level+'"></div>');                        dropdown.parent().parent().find('#'+results_div).html(html).slideDown('fast');                    }                }            });        }    };    //run the bootstrap    AUIU_Obj.init();});