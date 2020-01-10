{namespace name="documents/heidel_payment"}

{block name="documents_heidelpayment_payment_info"}
    <td style="width: 25%; padding: 0 10px;">
        <table style="{block name="documents_heidelpayment_footer_bank_data_table_style"}border: 1px solid grey; width: 100%{/block}">
            <tr>
                <td style="background-color: lightgray; width: 20%; padding-left: 8px">{s name="info/amount"}{/s}</td>
                <td style="padding-left: 8px">{$bankData.amount|currency}</td>
            </tr>
            <tr>
                <td style="background-color: lightgray; padding-left: 8px">{s name="info/holder"}{/s}</td>
                <td style="padding-left: 8px">{$bankData.holder}</td>
            </tr>
            <tr>
                <td style="background-color: lightgray; padding-left: 8px">{s name="info/iban"}{/s}</td>
                <td style="padding-left: 8px">{$bankData.iban}</td>
            </tr>
            <tr>
                <td style="background-color: lightgray; padding-left: 8px">{s name="info/bic"}{/s}</td>
                <td style="padding-left: 8px">{$bankData.bic}</td>
            </tr>
            <tr>
                <td style="background-color: lightgray; padding-left: 8px">{s name="info/descriptor"}{/s}</td>
                <td style="padding-left: 8px">{$bankData.descriptor}</td>
            </tr>
        </table>
    </td>
{/block}
