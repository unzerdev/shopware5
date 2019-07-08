{extends file="parent:frontend/checkout/finish.tpl"}

{block name='frontend_checkout_finish_items'}
    <div class="heidelpay--info-panel">
        <div class="panel has--border is--wide is--rounded">
            <div class="panel--title is--underline payment--title">
                Überweisungsinformationen
            </div>

            <div class="panel--body is--wide payment--content">
                Bitte überweisen Sie an folgende Bankverbindung den Betrag {$bankData.amount|currency} auf folgendes Konto

                <table>
                    <tr>
                        <td>Betrag</td>
                        <td>{$bankData.amount|currency}</td>
                    </tr>
                    <tr>
                        <td>Empfänger</td>
                        <td>{$bankData.holder}</td>
                    </tr>
                    <tr>
                        <td>IBAN</td>
                        <td>{$bankData.iban}</td>
                    </tr>
                    <tr>
                        <td>BIC</td>
                        <td>{$bankData.bic}</td>
                    </tr>
                    <tr>
                        <td>Verwendungszweck</td>
                        <td>{$bankData.descriptor}</td>
                    </tr>

                </table>
            </div>
        </div>
    </div>

    {$smarty.block.parent}
{/block}
