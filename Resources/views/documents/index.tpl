{extends file="parent:documents/index.tpl"}

{block name="document_index_footer"}
    {if ({config name="populate_document_invoice" namespace="heidel_payment"} == true && $heidelPaymentIsInvoice)
        || ({config name="populate_document_prepayment" namespace="heidel_payment"} == true && $heidelPaymentIsPrePayment)}
        <div id="footer" style="position: relative;">
            {include file="documents/heidel_payment_footer.tpl"}
        </div>
        {if !$smarty.foreach.pagingLoop.last}
            <pagebreak />
        {/if}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
