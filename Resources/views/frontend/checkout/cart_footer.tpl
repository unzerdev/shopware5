{extends file="parent:frontend/checkout/cart_footer.tpl"}

{block name='frontend_checkout_cart_footer_field_labels_total'}
    {if $sPayment.name === 'heidelHirePurchase'}
        {block name='frontend_checkout_cart_footer_heidelpay_interest'}
            <li id="heidelpay-interest" class="list--entry block-group entry--interest">
                {block name='frontend_checkout_cart_footer_heidelpay_interest_label'}
                    <div class="entry--label block">
                        {s name="HeidelpayCartFooterLabelInterest"}{/s}
                    </div>
                {/block}
                {block name='frontend_checkout_cart_footer_heidelpay_interest_value'}
                    <div class="entry--value block">
                        {if $heidelpay.interest}
                            {$heidelpay.interest|currency}{s name="Star" namespace="frontend/listing/box_article"}{/s}
                        {else}
                            {"0.00"|currency}{s name="Star" namespace="frontend/listing/box_article"}{/s}
                        {/if}
                    </div>
                {/block}
            </li>
        {/block}

        <li class="list--entry block-group entry--total {if $sPayment.name === 'heidelHirePurchase'}default-weight{/if}">
            {block name='frontend_checkout_cart_footer_field_labels_total_label'}
                <div class="entry--label block">
                    {s name="CartFooterLabelTotal"}{/s}
                </div>
            {/block}
            {block name='frontend_checkout_cart_footer_field_labels_total_value'}
                <div class="entry--value block is--no-star">
                    {if $sAmountWithTax && $sUserData.additional.charge_vat}{$sAmountWithTax|currency}{else}{$sAmount|currency}{/if}
                </div>
            {/block}
        </li>

        {block name='frontend_checkout_cart_footer_heidelpay_total_interest'}
            <li id="heidelpay-total-interest" class="list--entry block-group entry--total entry--total-with-interest">
                {block name='frontend_checkout_cart_footer_heidelpay_total_interest_label'}
                    <div class="entry--label block">
                        {s name="HeidelpayCartFooterLabelTotalInterest"}{/s}
                    </div>
                {/block}
                {block name='frontend_checkout_cart_footer_heidelpay_total_interest_value'}
                    <div class="entry--value block is--no-star">
                        {if $heidelpay.totalWithInterest}
                            {$heidelpay.totalWithInterest|currency}
                        {else}
                            {if $sAmountWithTax && $sUserData.additional.charge_vat}{$sAmountWithTax|currency}{else}{$sAmount|currency}{/if}
                        {/if}
                    </div>
                {/block}
            </li>
        {/block}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
