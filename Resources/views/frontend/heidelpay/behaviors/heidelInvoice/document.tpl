{block name="documents_heidelpay_heidelInvoice_bank_data"}
    {if {config name="populate_document_invoice" namespace="heidel_payment"} == true}
        <div style="{block name="documents_heidelpay_heidelInvoice_bank_data_wrapper_style"}border: 1px solid grey; padding: 16px; border-radius: 2px; margin-top: 16px{/block}">
            {block name="documents_heidelpay_heidelInvoice_bank_data_header"}
                <h3>{s name="title"}{/s}</h3>
            {/block}

            {block name="documents_heidelpay_heidelInvoice_bank_data_message"}
                <div>
                    {s name="message"}{/s}
                </div>
            {/block}
            {block name="documents_heidelpay_heidelInvoice_bank_data_table"}
                <table style="{block name="documents_heidelpay_heidelInvoice_bank_data_table_style"}border: 1px solid grey; margin-top: 16px; width: 100%{/block}">
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
            {/block}
        </div>
    {/if}
{/block}
