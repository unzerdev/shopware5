{extends file="parent:documents/index.tpl"}

{block name="document_index_info_ordercomment"}
    {$smarty.block.parent}

    {if $isUnzerPaymentPopulateAllowed}
        <pagebreak />
        {if $CustomDocument.UnzerPayment_Info}
            {eval var=$CustomDocument.UnzerPayment_Info.value}
        {/if}
    {/if}

    {if $showUnzerPaymentInstallmentInfo}
        <div>{s name="installment/info" namespace="documents/invoice/comment"}{/s}</div>
    {/if}
{/block}

{block name="document_index_footer"}
    {if $isUnzerPaymentPopulateAllowed}
        <div id="footer">
            {if $CustomDocument.UnzerPayment_Footer}
                {eval var=$CustomDocument.UnzerPayment_Footer.value}
            {/if}
        </div>
        {if !$smarty.foreach.pagingLoop.last}
            <pagebreak />
        {/if}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
