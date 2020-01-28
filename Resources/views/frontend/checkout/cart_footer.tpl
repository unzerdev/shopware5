{extends file="parent:frontend/checkout/cart_footer.tpl"}

{block name='frontend_checkout_cart_footer_field_labels_shipping'}
    {$smarty.block.parent}

    {if $sPayment.name === 'heidelHirePurchase'}
        <div id="heidelpay-interest" class="list--entry block-group entry--interest">
            {block name='frontend_checkout_cart_footer_field_labels_interest_label'}
                <div class="entry--label block">
                    {s name="HeidelpayCartFooterLabelInterest"}{/s}
                </div>
            {/block}
            {block name='frontend_checkout_cart_footer_field_labels_interest_value'}
                <div class="entry--value block">
                    {"0.00"|currency}{s name="Star" namespace="frontend/listing/box_article"}{/s}
                </div>
            {/block}
        </div>
    {/if}
{/block}


{block name='frontend_checkout_cart_footer_field_labels_total'}
    {$smarty.block.parent}

    {if $sPayment.name === 'heidelHirePurchase'}
        <div id="heidelpay-total-interest" class="list--entry block-group entry--total entry--total-with-interest">
            {block name='frontend_checkout_cart_footer_field_labels_total_interest_label'}
            <div class="entry--label block">
                {s name="HeidelpayCartFooterLabelTotalInterest"}{/s}
            </div>
            {/block}
            {block name='frontend_checkout_cart_footer_field_labels_total_interest_value'}
            <div class="entry--value block">
                {"0.00"|currency}{s name="Star" namespace="frontend/listing/box_article"}{/s}
            </div>
            {/block}
        </div>
    {/if}
{/block}
