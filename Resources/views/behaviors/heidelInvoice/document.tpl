{block name="documents_heidelpay_heidelInvoice_bank_data"}
    <div style="border: 1px solid grey; padding: 16px; border-radius: 2px; margin-top: 16px">
        <h3>{s name="title"}{/s}</h3>

        <div>
            {s name="message"}{/s}
        </div>

        <table style="border: 1px solid grey; margin-top: 16px; width: 100%">
            <tr>
                <td style="background-color: lightgray; width: 20%; padding-left: 8px">{s name="label/amount"}{/s}</td>
                <td style="padding-left: 8px">{$bankData.amount|currency}</td>
            </tr>
            <tr>
                <td style="background-color: lightgray; padding-left: 8px">{s name="label/recipient"}{/s}</td>
                <td style="padding-left: 8px">{$bankData.holder}</td>
            </tr>
            <tr>
                <td style="background-color: lightgray; padding-left: 8px">{s name="label/iban"}{/s}</td>
                <td style="padding-left: 8px">{$bankData.iban}</td>
            </tr>
            <tr>
                <td style="background-color: lightgray; padding-left: 8px">{s name="label/bic"}{/s}</td>
                <td style="padding-left: 8px">{$bankData.bic}</td>
            </tr>
            <tr>
                <td style="background-color: lightgray; padding-left: 8px">{s name="label/descriptor"}{/s}</td>
                <td style="padding-left: 8px">{$bankData.descriptor}</td>
            </tr>
        </table>
    </div>

{/block}
