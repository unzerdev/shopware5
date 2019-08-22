{extends file="parent:documents/index.tpl"}

{block name="document_index_info_ordercomment"}
    {$smarty.block.parent}

    {if $heidelBehaviorTemplate}
        <pagebreak />
        {include file="{$heidelBehaviorTemplate}"}
    {/if}
{/block}
