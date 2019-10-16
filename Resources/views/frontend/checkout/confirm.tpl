{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_checkout_confirm_information_wrapper"}
    {$smarty.block.parent}

    {if $hasHeidelpayFrame}
        {include file="frontend/heidelpay/checkout/confirm.tpl"}
    {/if}
{/block}

{block name='frontend_checkout_confirm_submit'}
    {if $hasHeidelpayFrame && $heidelpayInvalidConfig}
        <button type="submit" class="btn is--primary is--large right is--icon-right" form="confirm--form" data-preloader-button="true" disabled="disabled">
            {s name='ConfirmDoPayment'}{/s}<i class="icon--arrow-right"></i>
        </button>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
