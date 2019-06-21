{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_checkout_confirm_information_wrapper"}
    {$smarty.block.parent}

    {if $heidelPaymentFrame}
        {include file="frontend/heidelpay/checkout/confirm.tpl"}
    {/if}
{/block}

{block name="frontend_checkout_confirm_submit"}
    {if $heidelPaymentFrame}
        <button class="btn is--primary is--large right is--icon-right" form="confirm--form" data-preloader-button="true" id="heidelpay-submit-button" disabled="disabled">
            {s name='ConfirmActionSubmit'}{/s}<i class="icon--arrow-right"></i>
        </button>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
