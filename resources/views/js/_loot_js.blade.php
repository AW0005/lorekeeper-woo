
<script>
$( document ).ready(function() {    
    var $lootTable  = $('#lootTableBody');
    var $lootRow = $('#lootRow').find('.loot-row');
    var $itemSelect = $('#lootRowData').find('.item-select');
    var $PetSelect = $('#lootRowData').find('.pet-select');
    var $WeaponSelect = $('#lootRowData').find('.weapon-select');
    var $GearSelect = $('#lootRowData').find('.gear-select');
    var $currencySelect = $('#lootRowData').find('.currency-select');
    @if($showLootTables)
        var $tableSelect = $('#lootRowData').find('.table-select');
    @endif
    @if($showRaffles)
        var $raffleSelect = $('#lootRowData').find('.raffle-select');
    @endif

    $('#lootTableBody .selectize').selectize();
    attachRemoveListener($('#lootTableBody .remove-loot-button'));

    $('#addLoot').on('click', function(e) {
        e.preventDefault();
        var $clone = $lootRow.clone();
        $lootTable.append($clone);
        attachRewardTypeListener($clone.find('.reward-type'));
        attachRemoveListener($clone.find('.remove-loot-button'));
    });
    
    
    function onPetSelect (e) {
        var val = $(this).val();
        var $cell = $(this).parent().parent().find('.loot-row-select');
        var clone = $('#lootRowData').find('.pet-variant-select-' + val);
        
        $cell.find('select:not(.pet-select)').remove();
        $cell.append(clone);
    }
    
    $PetSelect.on('change', onPetSelect);
    $('.loot-row-select .pet-select').on('change', onPetSelect);

    $('.reward-type').on('change', function(e) {
        var val = $(this).val();
        var $cell = $(this).parent().parent().find('.loot-row-select');

        var $clone = null;
        if(val == 'Item') $clone = $itemSelect.clone();
        else if (val == 'Currency') $clone = $currencySelect.clone();
        else if (val == 'Pet') $clone = $PetSelect.clone(true);
        else if (val == 'Weapon') $clone = $WeaponSelect.clone();
        else if (val == 'Gear') $clone = $GearSelect.clone();
        @if($showLootTables)
            else if (val == 'LootTable') $clone = $tableSelect.clone();
        @endif
        @if($showRaffles)
            else if (val == 'Raffle') $clone = $raffleSelect.clone();
        @endif

        $cell.html('');
        $cell.append($clone);
    });

    function attachRewardTypeListener(node) {
        node.on('change', function(e) {
            var val = $(this).val();
            var $cell = $(this).parent().parent().find('.loot-row-select');

            var $clone = null;
            if(val == 'Item') $clone = $itemSelect.clone();
            else if (val == 'Currency') $clone = $currencySelect.clone();
            else if (val == 'Pet') $clone = $PetSelect.clone(true);
            else if (val == 'Weapon') $clone = $WeaponSelect.clone();
            else if (val == 'Gear') $clone = $GearSelect.clone();
            @if($showLootTables)
                else if (val == 'LootTable') $clone = $tableSelect.clone();
            @endif
            @if($showRaffles)
                else if (val == 'Raffle') $clone = $raffleSelect.clone();
            @endif

            $cell.html('');
            $cell.append($clone);
            $clone.selectize();
        });
    }

    function attachRemoveListener(node) {
        node.on('click', function(e) {
            e.preventDefault();
            $(this).parent().parent().remove();
        });
    }

});
    
</script>