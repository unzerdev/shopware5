{namespace name="documents/heidel_payment/footer"}

{block name="documents_heidelpayment_footer"}
    <table style="vertical-align: top;" width="100%" border="0">
        <tbody>
        <tr valign="top">
            {block name="documents_heidelpayment_footer_shop_data"}
                <td style="width: 25%; padding: 0 10px;">
                    <p><span style="font-size: xx-small;">{s name="company_name"}{/s} {config name="company"}</span></p>
                    <p><span style="font-size: xx-small;">{s name="company_mail"}{/s} {config name="mail"}</span></p>
                </td>
            {/block}
            {block name="documents_heidelpayment_footer_payment"}
                <td style="width: 25%; padding: 0 10px;">
                    <table style="{block name="documents_heidelpayment_footer_bank_data_table_style"}border: 1px solid grey; width: 100%{/block}">
                        <tr>
                            <td style="background-color: lightgray; width: 20%; padding-left: 8px">{s name="amount"}{/s}</td>
                            <td style="padding-left: 8px">{$bankData.amount|currency}</td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray; padding-left: 8px">{s name="recipient"}{/s}</td>
                            <td style="padding-left: 8px">{$bankData.holder}</td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray; padding-left: 8px">{s name="iban"}{/s}</td>
                            <td style="padding-left: 8px">{$bankData.iban}</td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray; padding-left: 8px">{s name="bic"}{/s}</td>
                            <td style="padding-left: 8px">{$bankData.bic}</td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray; padding-left: 8px">{s name="descriptor"}{/s}</td>
                            <td style="padding-left: 8px">{$bankData.descriptor}</td>
                        </tr>
                    </table>
                </td>
            {/block}
            {block name="documents_heidelpayment_footer_address"}
                <td style="width: 25%; padding: 0 10px;">
                    <p><span style="font-size: xx-small;">{s name="address"}{/s}</span></p>
                    <p><span style="font-size: xx-small;">{config name="address"}</span></p>
                </td>
            {/block}
            {block name="documents_heidelpayment_footer_additional"}
            {/block}
        </tr>
        </tbody>
    </table>
{/block}
