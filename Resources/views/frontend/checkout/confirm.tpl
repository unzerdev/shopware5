{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_checkout_confirm_information_wrapper"}
    {$smarty.block.parent}

    {if $unzerPaymentFrame}
        {include file="frontend/unzer_payment/checkout/confirm.tpl"}
    {/if}
{/block}
