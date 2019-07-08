{extends file="parent:frontend/checkout/finish.tpl"}

{block name='frontend_checkout_finish_items'}
    <div class="heidelpay--info-panel">
        <div class="panel has--border is--wide is--rounded">
            <div class="panel--title is--underline payment--title">
                {s name="title"}{/s}
            </div>

            <div class="panel--body is--wide payment--content">
                {s name="message"}{/s}

                <table>
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
                        <td>{s name="label/BIC"}{/s}</td>
                        <td>{$bankData.bic}</td>
                    </tr>
                    <tr>
                        <td>{s name="label/descriptor"}{/s}</td>
                        <td>{$bankData.descriptor}</td>
                    </tr>

                </table>
            </div>
        </div>
    </div>

    {$smarty.block.parent}
{/block}
