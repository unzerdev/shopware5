{namespace name="documents/heidel_payment"}

{block name="documents_heidelpayment_footer"}
    <table style="vertical-align: top;" width="100%" border="0">
        <tbody>
        <tr valign="top">
            {block name="documents_heidelpayment_footer_shop_data"}
                <td style="width: 25%;">
                    {block name="documents_heidelpayment_footer_shop_data_inner"}
                        <p><span style="font-size: xx-small;">Demo GmbH</span></p>
                        <p><span style="font-size: xx-small;">Steuer-Nr <br />UST-ID: <br />Finanzamt </span><span style="font-size: xx-small;">Musterstadt</span></p>
                    {/block}
                </td>
            {/block}
            {block name="documents_heidelpayment_footer_payment"}
                <td style="width: 25%;">
                    {block name="documents_heidelpayment_footer_payment_inner"}
                        <p><span style="font-size: xx-small;">Bankverbindung</span></p>
                        <p><span style="font-size: xx-small;">
                                {s name="info/holder"}{/s} {$bankData.holder} <br />
                                {s name="info/iban"}{/s} {$bankData.iban} <br />
                                {s name="info/bic"}{/s} {$bankData.bic} <br />
                            </span></p>
                    {/block}
                </td>
            {/block}
            {block name="documents_heidelpayment_footer_address"}
                <td style="width: 25%;">
                    {block name="documents_heidelpayment_footer_address_inner"}
                        <p><span style="font-size: xx-small;">AGB<br /></span></p>
                        <p><span style="font-size: xx-small;">Gerichtsstand ist Musterstadt<br />Erf&uuml;llungsort Musterstadt<br />Gelieferte Ware bleibt bis zur vollst&auml;ndigen Bezahlung unser Eigentum</span></p>
                    {/block}
                </td>
            {/block}
            {block name="documents_heidelpayment_footer_additional"}
                <td style="width: 25%;">
                    {block name="documents_heidelpayment_footer_additional_inner"}
                        <p><span style="font-size: xx-small;">Gesch&auml;ftsf&uuml;hrer</span></p>
                        <p><span style="font-size: xx-small;">Max Mustermann</span></p>
                    {/block}
                </td>
            {/block}
        </tr>
        </tbody>
    </table>
{/block}
