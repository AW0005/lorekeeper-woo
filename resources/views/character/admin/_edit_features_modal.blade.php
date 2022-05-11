{!! Form::open(['url' => 'admin/character/image/'.$image->id.'/traits']) !!}
    <div class="form-group">
        {!! Form::label('Species') !!}
        {!! Form::select('species_id', $specieses, $image->species_id, ['class' => 'form-control', 'id' => 'species']) !!}
    </div>

    <div class="form-group" id="subtypes">
        {!! Form::label('Subtype (Optional)') !!}
        {!! Form::select('subtype_id', $subtypes, $image->subtype_id, ['class' => 'form-control', 'id' => 'subtype']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('Character Rarity') !!}
        {!! Form::select('rarity_id', $rarities, $image->rarity_id, ['class' => 'form-control']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('Traits') !!}
        <div id="featureList">
            @foreach($image->features as $feature)
                <div class="d-flex mb-2">
                    {!! Form::select('feature_id[]', $features, $feature->feature_id, ['class' => 'form-control mr-2 feature-select original', 'placeholder' => 'Select Trait']) !!}
                    {!! Form::text('feature_data[]', $feature->data, ['class' => 'form-control mr-2', 'placeholder' => 'Extra Info (Optional)']) !!}
                    <a href="#" class="remove-feature btn btn-danger mb-2"><i class="fas fa-times"></i></a>
                </div>
            @endforeach
        </div>
        <div><a href="#" class="btn btn-primary" id="add-feature">Add Trait</a></div>
        <div class="feature-row hide mb-2">
            {!! Form::select('feature_id[]', $features, null, ['class' => 'form-control mr-2 feature-select', 'placeholder' => 'Select Trait']) !!}
            {!! Form::text('feature_data[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Extra Info (Optional)']) !!}
            <a href="#" class="remove-feature btn btn-danger mb-2"><i class="fas fa-times"></i></a>
        </div>
    </div>

    <div class="text-right">
        {!! Form::submit('Edit', ['class' => 'btn btn-primary']) !!}
    </div>
{!! Form::close() !!}

<script>
    $(document).ready(function() {
        const features = <?php echo json_encode($features) ?>;
        const species_id = <?php echo $image->species_id ?>;

        const getFeatureOptions = (species) => {
            const arry = [];
            const groups = [];
            for(const [key, cat] of Object.entries(features)) {
                const catObj = [];
                groups.push(key);
                for(feat in cat) {
                    if((!species || cat[feat].species_id === species)) {
                        arry.push({text: cat[feat].name, value: cat[feat].id, optgroup: key });
                    }
                }
            }

            arry.reverse()
            return {features: arry, groups};
        }

        const org = $('.original.feature-select');
        org.selectize({
            render: {
                item: featureSelectedRender
            }
        });

        org.each(select => {
            const selectize = org[select].selectize;
            if(selectize) {
                const {features, groups} = getFeatureOptions(species_id);

                const selected = selectize.items[0];
                selectize.clear()
                selectize.clearOptions();
                groups.forEach(group => selectize.addOptionGroup(group, {label: group}));
                selectize.addOption(features);
                selectize.refreshOptions(false);
                selectize.addItem(selected);

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
                    item: featureSelectedRender
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
            return '<div><span>' + escape(item["text"].trim()) + ' (' + escape(item["optgroup"].trim()) + ')' + '</span></div>';
        }
    });

    $( "#species" ).change(function() {
      var species = $('#species').val();
      var id = '<?php echo($image->id); ?>';
      $.ajax({
        type: "GET", url: "{{ url('admin/character/image/traits/subtype') }}?species="+species+"&id="+id, dataType: "text"
      }).done(function (res) { $("#subtypes").html(res); }).fail(function (jqXHR, textStatus, errorThrown) { alert("AJAX call failed: " + textStatus + ", " + errorThrown); });

    });

</script>
