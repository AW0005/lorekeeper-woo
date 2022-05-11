<script>
    $(document).ready(function() {
        // Code generation ////////////////////////////////////////////////////////////////////////////

        var codeFormat = "{{ Config::get('lorekeeper.settings.character_codes') }}";
        var $code = $('#code');
        var $number = $('#number');
        var $year = $('#year');
        var $category = $('#category');

        $number.on('keyup', function() {
            updateCode();
        });
        $category.on('change', function() {
            updateCode();
        });
        $year.on('input', () => updateCode());

        function updateCode() {
            var str = codeFormat;
            str = str.replace('{category}', $category.find(':selected').data('code'));
            str = str.replace('{number}', $number.val());
            str = str.replace('{year}', $year.val());
            $code.val(str);
        }

        // Pull number ////////////////////////////////////////////////////////////////////////////////

        var $pullNumber = $('#pull-number');
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
