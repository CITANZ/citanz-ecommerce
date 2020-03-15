<div class="section">
    <div class="container">
        <h1 class="title is-1">$Title</h1>
        <% if $LastProcessedCart %>
        <% with $LastProcessedCart %>
        <div class="columns">
            <article class="column">
                <div class="columns is-multiline">
                    <div class="column is-6">
                        <h2 class="title is-5">Shipping</h2>
                        <div class="address-detail">
                            <p>$ShippingFirstname $ShippingSurname</p>
                            <p>$ShippingPhone</p>
                            <% if $ShippingOrganisation %>
                            <p>$ShippingOrganisation</p>
                            <% end_if %>
                            <p><% if $ShippingApartment %>$ShippingApartment, <% end_if %> $ShippingAddress</p>
                            <p>$ShippingSuburb, $ShippingTown, $ShippingRegion</p>
                            <p>$Top.TranslateCountry($ShippingCountry), $ShippingPostcode</p>
                        </div>
                    </div>
                    <div class="column is-6">
                        <h2 class="title is-5">Billing</h2>
                        <div class="address-detail">
                            <p>$BillingFirstname $BillingSurname</p>
                            <p>$BillingPhone</p>
                            <% if $BillingOrganisation %>
                            <p>$BillingOrganisation</p>
                            <% end_if %>
                            <p><% if $BillingApartment %>$BillingApartment, <% end_if %> $BillingAddress</p>
                            <p>$BillingSuburb, $BillingTown, $BillingRegion</p>
                            <p>$Top.TranslateCountry($BillingCountry), $BillingPostcode</p>
                        </div>
                    </div>
                    <div class="column is-12">
                        <h2 class="title is-5">Email</h2>
                        <p>$Email</p>
                    </div>
                </div>
                <table class="table is-fullwidth">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="has-text-centered">Qty</th>
                            <th class="has-text-right" style="width: 25%;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <% loop $Items %>
                        <tr>
                            <td>$ShowTitle</td>
                            <td class="has-text-centered">$Quantity</td>
                            <td class="has-text-right">
                                $Subtotal.Nice
                                <% if $Up.Discount && $NoDiscount %><br /><em>Non-discountable</em><% end_if %>
                                <% if $isExempt %><br /><em>Non-taxable</em><% end_if %>
                            </td>
                        </tr>
                        <% end_loop %>
                    </tbody>
                    <tfoot>
                        <% if $Discount %>
                        <tr>
                            <td colspan="2">$Discount.Title: $Discount.Description</td>
                            <td class="has-text-right">-${$Discounted}</td>
                        </tr>
                        <% end_if %>
                        <tr v-if="site_data.cart.gst">
                            <td colspan="2">GST</td>
                            <td class="has-text-right">${$GST}</td>
                        </tr>
                        <tr v-if="site_data.freight">
                            <td colspan="2">Shipping: $Freight.Title</td>
                            <td class="has-text-right">$ShippingCost.Nice</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="has-text-right">Grand Total:</td>
                            <td class="has-text-right">$PayableTotal.Nice</td>
                        </tr>
                    </tfoot>
                </table>
                <% if $Comment %>
                <div class="content">
                    <p><strong>Comment</strong><br />
                    $Comment<br /><br /></p>
                </div>
                <% end_if %>
            </article>
            <aside class="column is-4">
                <p class="subtitle is-6">Amount paid</p>
                <p class="title is-2">$Payments.First.Amount.Nice</p>
                <dl class="payment-details">
                    <dt><strong>Reference No.</strong></dt>
                    <dd>$CustomerReference</dd>
                    <% with $Payments.First %>
                    <% if $CardType %>
                    <dt><strong>Card Type</strong></dt>
                    <dd>$CardType</dd>
                    <% end_if %>
                    <% if $CardNumber %>
                    <dt><strong>Card No.</strong></dt>
                    <dd>$CardNumber</dd>
                    <% end_if %>
                    <% if $CardExpiry %>
                    <dt><strong>Card Expiry</strong></dt>
                    <dd>$CardExpiry</dd>
                    <% end_if %>
                    <% if $CardHolder %>
                    <dt><strong>Card Holder</strong></dt>
                    <dd>$CardHolder</dd>
                    <% end_if %>
                    <% end_with %>
                </dl>
                <hr />
                <p class="help">Paid at $Payments.First.Created.Nice, with <strong>$Payments.First.PaymentMethod</strong></p>
                <hr />
                <% if $Payments.First.Status == 'Cancelled' || $Payments.First.Status == 'Pending' || $Payments.First.Status == 'Failed' %>
                    <a class="button is-info" href="/cart">Try again</a>
                <% else %>
                    <a class="button is-info" href="$Top.CatalogLink">Keep shopping</a>
                <% end_if %>
            </aside>
        </div>
        <% end_with %>
        <% end_if %>
    </div>
</div>
