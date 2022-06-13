{!! Form::open(['url' => 'admin/character/image/'.$image->id.'/reupload', 'files' => true]) !!}
<div class="form-group">
        {!! Form::label('Character Image') !!} {!! add_help('This is the full masterlist image. Note that the image is not protected in any way, so take precautions to avoid art/design theft.') !!}
        <div>{!! Form::file('image', ['id' => 'mainImage']) !!}</div>
    </div>

    <div class="text-right">
        {!! Form::submit('Edit', ['class' => 'btn btn-primary']) !!}
    </div>
{!! Form::close() !!}

<script>
    $(document).ready(function() {
        //$('#useCropper').bootstrapToggle();

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

        function updateCropValues() {
            var values = c.get();
            $x0.val(values.points[0]);
            $y0.val(values.points[1]);
            $x1.val(values.points[2]);
            $y1.val(values.points[3]);
        }
    });

</script>
