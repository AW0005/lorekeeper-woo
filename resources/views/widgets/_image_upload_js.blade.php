<script>
$( document ).ready(function() {
    const features = <?php echo json_encode($features) ?>;

    const getFeatureOptions = (species) => {
        const arry = [];
        const groups = [];
        for(const [key, cat] of Object.entries(features)) {
            const catObj = [];
            groups.push(key);
            for(feat in cat) {
                if((!species || cat[feat].species_id === species)) {
                    arry.push({text: cat[feat].simpleName, value: cat[feat].id, optgroup: key });
                }
            }
        }

        return {features: arry, groups};
    }

    // Cropper ////////////////////////////////////////////////////////////////////////////////////

    var $useCropper = $('#useCropper');
    var $thumbnailCrop = $('#thumbnailCrop');
    var $thumbnailUpload = $('#thumbnailUpload');

    var useCropper = $useCropper.is(':checked');

    updateCropper();

    $useCropper.on('change', function(e) {
        useCropper = $useCropper.is(':checked');

        updateCropper();
    });

    function updateCropper() {
        if(useCropper) {
            $thumbnailUpload.addClass('hide');
            $thumbnailCrop.removeClass('hide');
        }
        else {
            $thumbnailCrop.addClass('hide');
            $thumbnailUpload.removeClass('hide');
        }
    }

    // Designers and artists //////////////////////////////////////////////////////////////////////

    $('.add-designer').on('click', function(e) {
        e.preventDefault();
        addDesignerRow($(this));
    });
    function addDesignerRow($trigger) {
        var $clone = $('.designer-row').clone();
        $('#designerList').append($clone);
        $clone.removeClass('hide designer-row');
        $clone.addClass('d-flex');
        $clone.find('.add-designer').on('click', function(e) {
            e.preventDefault();
            addDesignerRow($(this));
        })
        $trigger.css({ visibility: 'hidden' });
        $clone.find('.designer-select').selectize();
    }

    $('.add-artist').on('click', function(e) {
        e.preventDefault();
        addArtistRow($(this));
    });
    function addArtistRow($trigger) {
        var $clone = $('.artist-row').clone();
        $('#artistList').append($clone);
        $clone.removeClass('hide artist-row');
        $clone.addClass('d-flex');
        $clone.find('.add-artist').on('click', function(e) {
            e.preventDefault();
            addArtistRow($(this));
        })
        $trigger.css({ visibility: 'hidden' });
        $clone.find('.artist-select').selectize();
    }

    // Traits /////////////////////////////////////////////////////////////////////////////////////

    $('.initial.feature-select').selectize({
        render: {
            item: featureSelectedRender,
            option: (opt) => `<div class="option" data-selectable="" data-value="${opt['value']}">${opt['text']}</div>`
        }
    });
    $('#add-feature').on('click', function(e) {
        e.preventDefault();
        addFeatureRow();
    });
    $('.remove-feature').on('click', function(e) {
        e.preventDefault();
        removeFeatureRow($(this));
    })
    function addFeatureRow() {
        var $clone = $('.feature-row').clone();
        $('#featureList').append($clone);
        $clone.removeClass('hide feature-row');
        $clone.addClass('d-flex');
        $clone.find('.remove-feature').on('click', function(e) {
            e.preventDefault();
            removeFeatureRow($(this));
        })
        const selects = $clone.find('.feature-select');

        selects.selectize({
            render: {
                item: featureSelectedRender,
                option: (opt) => `<div class="option" data-selectable="" data-value="${opt['value']}">${opt['text']}</div>`
            }
        });

        selects.each(select => {
            const selectize = selects[select].selectize;
            if(selectize) {
                const {features, groups} = getFeatureOptions(parseInt($('#species').val(), 10));

                const selected = selectize.items[0];
                selectize.clear()
                selectize.clearOptions();
                groups.forEach(group => selectize.addOptionGroup(group, {label: group}));
                selectize.addOption(features);
                selectize.refreshOptions(false);
                selectize.addItem(selected);
            }
        });
    }
    function removeFeatureRow($trigger) {
        $trigger.parent().remove();
    }
    function featureSelectedRender(item, escape) {
        return '<div><span>' + item["text"].trim() + '<span class="subdued"> [' + escape(item["optgroup"].trim()) + ']</span>' + '</span></div>';
    }

    // Croppie ////////////////////////////////////////////////////////////////////////////////////

    var thumbnailWidth = {{ Config::get('lorekeeper.settings.masterlist_thumbnails.width') }};
    var thumbnailHeight = {{ Config::get('lorekeeper.settings.masterlist_thumbnails.height') }};
    var $cropper = $('#cropper');
    var c = null;
    var $x0 = $('#cropX0');
    var $y0 = $('#cropY0');
    var $x1 = $('#cropX1');
    var $y1 = $('#cropY1');
    var zoom = 0;

    @if(isset($useUploaded) && $useUploaded)
        // This is for modification of an existing image:
        c = new Croppie($cropper[0], {
            viewport: {
                width: thumbnailWidth,
                height: thumbnailHeight
            },
            boundary: { width: thumbnailWidth + 100, height: thumbnailHeight + 100 },
            update: function() {
                updateCropValues();
            }
        });
        c.bind({
            url: $cropper.data('url'),
            // points: [$x0.val(),$x1.val(),$y0.val(),$y1.val()], // this does not work
        }).then(function() {
            updateCropValues();
        });
        console.log(($x1.val() - $x0.val()) / thumbnailWidth);
    @else
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $cropper.attr('src', e.target.result);
                    c = new Croppie($cropper[0], {
                        viewport: {
                            width: thumbnailWidth,
                            height: thumbnailHeight
                        },
                        boundary: { width: thumbnailWidth + 100, height: thumbnailHeight + 100 },
                        update: function() {
                            updateCropValues();
                        }
                    });
        console.log(c);
                    updateCropValues();
                    $('#cropSelect').addClass('hide');
                    $cropper.removeClass('hide');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#mainImage").change(function() {
            readURL(this);
        });
    @endif

    function updateCropValues() {
        var values = c.get();
        console.log(values);
        //console.log([$x0.val(),$x1.val(),$y0.val(),$y1.val()]);
        $x0.val(values.points[0]);
        $y0.val(values.points[1]);
        $x1.val(values.points[2]);
        $y1.val(values.points[3]);
    }


});

</script>
