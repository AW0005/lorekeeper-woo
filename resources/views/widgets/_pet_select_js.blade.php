<script>
    $(document).ready(function() {
        var $userPetCategory = $('#userPetCategory');
        $userPetCategory.on('change', function(e) {
            refreshCategory();
        });
        $('.pet-stack').on('click', function(e) {
            var $parent = $(this).parent().parent().parent();
            $parent.toggleClass('category-selected');
            $parent.find('.pet-checkbox').prop('checked', $parent.hasClass('category-selected'));
            //refreshCategory();
        });
        $('.pet-info').on('click', function(e) {
            e.preventDefault();
            var $parent = $(this).parent().parent().parent();
            loadModal("{{ url('pets') }}/" + $parent.data('id') + "?read_only={{ isset($readOnly) && $readOnly ? 1 : 0 }}", $parent.data('name'));
        });
        $('.pet-select-all').on('click', function(e) {
            e.preventDefault();
            var $target = $('.user-pet:not(.hide)');
            $target.addClass('category-selected');
            $target.find('.pet-checkbox').prop('checked', true);
        });
        $('.pet-clear-selection').on('click', function(e) {
            e.preventDefault();
            var $target = $('.user-pet:not(.hide)');
            $target.removeClass('category-selected');
            $target.find('.pet-checkbox').prop('checked', false);
        });

        function refreshCategory() {
            var display = $userPetCategory.val();
            $('.user-pet').addClass('hide');
            $('.user-pets .category-' + display).removeClass('hide');
        }
    });
</script>