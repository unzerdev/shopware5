{extends file='parent:frontend/index/header.tpl'}

{block name='frontend_index_header_javascript_modernizr_lib'}
    {include file="frontend/_includes/heidelpay_libraries.tpl"}
{/block}

{block name="frontend_index_header_css_screen"}
    {$smarty.block.parent}

    <link rel="stylesheet" href="https://static.heidelpay.com/v1/heidelpay.css" />
{/block}
