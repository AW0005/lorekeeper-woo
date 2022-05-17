<div class="card mb-3 stock {{ $stock ? '' : 'hide' }}">
    <div class="p-3">
        <div class="row" style="align-items: flex-start;">
            <a class="col-1" data-toggle="collapse" href="#collapsable-{{$key}}">
                <i class="fas fa-angle-down" style="font-size: 24px"></i>
            </a>
            <div class="col-7">
                {!! Form::label('item_id['.$key.']', 'Item') !!}
                {!! Form::select('item_id['.$key.']', $items, $stock ? $stock->item_id : null, ['class' => 'form-control stock-field selectize', 'data-name' => 'item_id']) !!}
            </div>
            <div class="col-2">
                {!! Form::label('cost['.$key.']', 'Cost (CC)') !!}
                {!! Form::text('cost['.$key.']', $stock ? $stock->cost : null, ['class' => 'form-control stock-field', 'data-name' => 'cost']) !!}
            </div>
            <a href="#" class="col-2 remove-stock-button btn btn-danger">Remove</a>
        </div>
        <!-- Hidden form fields I don't need but I don't feel like defaulting them in the service -->
        <div class="row hide">
            <div class="hide">
                {!! Form::select('currency_id['.$key.']', $currencies, $stock ? $stock->currency_id : null, ['class' => 'form-control stock-field selectize', 'data-name' => 'currency_id']) !!}
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::checkbox('use_user_bank['.$key.']', 1, $stock ? $stock->use_user_bank : 1, ['class' => 'form-check-input stock-toggle stock-field', 'data-name' => 'use_user_bank']) !!}
                    {!! Form::label('use_user_bank['.$key.']', 'Use User Bank', ['class' => 'form-check-label ml-3']) !!} {!! add_help('This will allow users to purchase the item using the currency in their accounts, provided that users can own that currency.') !!}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::checkbox('use_character_bank['.$key.']', 1, $stock ? $stock->use_character_bank : 0, ['class' => 'form-check-input stock-toggle stock-field', 'data-name' => 'use_character_bank']) !!}
                    {!! Form::label('use_character_bank['.$key.']', 'Use Character Bank', ['class' => 'form-check-label ml-3']) !!} {!! add_help('This will allow users to purchase the item using the currency belonging to characters they own, provided that characters can own that currency.') !!}
                </div>
            </div>
        </div>
        <div id="collapsable-{{$key}}" class="collapse">
            <div class="row mt-3">
                <div class="form-group col-4 mb-4">
                    <div>{!! Form::label('is_limited_stock['.$key.']', 'Set Limited Stock') !!} {!! add_help('If turned on, will limit the amount purchaseable to the quantity set below.') !!}</div>
                    {!! Form::checkbox('is_limited_stock['.$key.']', 1, $stock ? $stock->is_limited_stock : false, ['class' => 'form-check-input stock-limited stock-toggle stock-field', 'data-name' => 'is_limited_stock']) !!}
                </div>
                <div class="form-group col-8 stock-limited-quantity {{ $stock && $stock->is_limited_stock ? '' : 'hide' }}">
                    {!! Form::label('quantity['.$key.']', 'Quantity') !!} {!! add_help('If left blank, will be set to 0 (sold out).') !!}
                    {!! Form::text('quantity['.$key.']', $stock ? $stock->quantity : 0, ['class' => 'form-control stock-field', 'data-name' => 'quantity']) !!}
                </div>
            </div>
            <div>
                {!! Form::label('purchase_limit['.$key.']', 'User Purchase Limit') !!} {!! add_help('This is the maximum amount of this item a user can purchase from this shop. Set to 0 to allow infinite purchases.') !!}
                {!! Form::text('purchase_limit['.$key.']', $stock ? $stock->purchase_limit : 0, ['class' => 'form-control stock-field', 'data-name' => 'purchase_limit']) !!}
            </div>
    </div>
    </div>
</div>
