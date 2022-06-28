<script>
    $(document).ready(function() {
        // Code generation ////////////////////////////////////////////////////////////////////////////

        var codeFormat = "{{ Config::get('lorekeeper.settings.character_codes') }}";

        var $pullNumber = $('#pull-number');
        var $code = $('#code');
        var $number = $('#number');
        var $year = $('#year');
        var $category = $('#category');
        var $holocategory = $('#holocategory');
        var $holonumber = $('#holonumber');
        var $holocode = $('#holocode');

        $number.on('keyup', function() {
            updateCode();
        });
        $category.on('change', function() {
            $.get( "{{ url('admin/masterlist/get-number') }}?category=" + $category.val() + "&year=" + $year.val(), function( data ) {
                $number.val( data );
                $pullNumber.prop('disabled', false);
                updateCode();
            });
        });
        $year.on('input', () => updateCode());


        $holocategory.on('change', function() {
            console.log($holocategory.val());
            $.get( "{{ url('admin/masterlist/get-number') }}?category=" + $holocategory.val() + "&year=" + $year.val(), function( data ) {
                console.log(data)
                $holonumber.val( data );
                updateHoloCode();
            });
        });

        function updateCode() {
            var str = codeFormat;
            str = str.replace('{category}', $category.find(':selected').data('code'));
            str = str.replace('{number}', $number.val());
            str = str.replace('{year}', $year.val());
            $code.val(str);
        }

        function updateHoloCode() {
            var str = codeFormat;
            str = str.replace('{category}', $holocategory.find(':selected').data('code'));
            str = str.replace('{number}', $holonumber.val());
            str = str.replace('{year}', $year.val());
            $holocode.val(str);
        }

        // Pull number ////////////////////////////////////////////////////////////////////////////////
        $pullNumber.on('click', function(e) {
            e.preventDefault();
            $pullNumber.prop('disabled', true);
            $.get( "{{ url('admin/masterlist/get-number') }}?category=" + $category.val() + "&year=" + $year.val(), function( data ) {
                $number.val( data );
                $pullNumber.prop('disabled', false);
                updateCode();
            });
        });
    });
</script>
