<div id="cita-ecom-discount-holder">
    <div id="cita-ecom-discount">
        <input ref="discount_id" type="hidden" value="$DiscountID" />
        <input ref="existings" type="hidden" value="$Existings" />
        <div class="form-group field text">
            <label for="cita-ecom-discount--search" class="form__field-label">Search product</label>
            <div class="form__field-holder">
                <input type="text" v-model="search_term" class="text" id="cita-ecom-discount--search" />
                <ul class="candidates" v-if="candidates && candidates.length > 0">
                    <li v-for="candidate in candidates">
                        <a href="#" @click.prevent="addProduct(candidate.id)">{{ candidate.title }}</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="form-group field text" v-if="existings">
            <label class="form__field-label">Applied to products:</label>
            <div class="form__field-holder">
                <table class="table table-striped">
                    <tbody>
                        <tr v-for="product in existings">
                        <td style="vertical-align: middle;">{{ product.title }}</td>
                        <td width="10%"><button type="button" class="btn btn-danger" @click.prevent="removeProduct(product.id)">Remvoe</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
