{extends file="parent:frontend/checkout/finish.tpl"}

{block name='frontend_checkout_finish_items'}
    {block name="frontend_checkout_finish_unzer_payment_Prepayment_bank_data"}
        <div class="unzer-payment--info-panel">
            <div class="panel has--border is--wide is--rounded">
                {block name="frontend_checkout_finish_unzer_payment_Prepayment_bank_data_title"}
                    <div class="panel--title is--underline payment--title">
                        {s name="title"}{/s}
                    </div>
                {/block}

                {block name="frontend_checkout_finish_unzer_payment_Prepayment_bank_body"}
                    <div class="panel--body is--wide payment--content">

                        {block name="frontend_checkout_finish_unzer_payment_Prepayment_bank_body_message"}
                            <div>
                                {s name="message"}{/s}
                            </div>
                        {/block}

                        {block name="frontend_checkout_finish_unzer_payment_Prepayment_bank_body_table"}
                            <table class="unzer-payment--table bank-data">
                                <tr>
                                    <td>{s name="label/amount"}{/s}</td>
                                    <td>{$bankData.amount|currency}</td>
                                </tr>
                                <tr>
                                    <td>{s name="label/recipient"}{/s}</td>
                                    <td>{$bankData.holder}</td>
                                </tr>
                                <tr>
                                    <td>{s name="label/iban"}{/s}</td>
                                    <td>{$bankData.iban}</td>
                                </tr>
                                <tr>
                                    <td>{s name="label/bic"}{/s}</td>
                                    <td>{$bankData.bic}</td>
                                </tr>
                                <tr>
                                    <td>{s name="label/descriptor"}{/s}</td>
                                    <td>{$bankData.descriptor}</td>
                                </tr>
                            </table>
                        {/block}
                    </div>
                {/block}
            </div>
        </div>
    {/block}
    {$smarty.block.parent}
{/block}
