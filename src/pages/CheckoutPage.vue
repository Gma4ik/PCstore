<template>
  <div class="checkout-page page">
    <div class="checkout-wrapper">
      <!-- Left: Form -->
      <div class="checkout-left">
        <h1 class="checkout-title">Оформлення замовлення</h1>

        <!-- Steps -->
        <div class="steps">
          <div class="step" :class="{ active: step >= 1, done: step > 1 }">
            <div class="step-circle">
              <span v-if="step > 1">✓</span>
              <span v-else>1</span>
            </div>
            <span class="step-label">Контакти</span>
          </div>
          <div class="step-line" :class="{ active: step > 1 }"></div>
          <div class="step" :class="{ active: step >= 2, done: step > 2 }">
            <div class="step-circle">
              <span v-if="step > 2">✓</span>
              <span v-else>2</span>
            </div>
            <span class="step-label">Доставка</span>
          </div>
          <div class="step-line" :class="{ active: step > 2 }"></div>
          <div class="step" :class="{ active: step >= 3 }">
            <div class="step-circle">3</div>
            <span class="step-label">Підтвердження</span>
          </div>
        </div>

        <form @submit.prevent="handleNext" class="checkout-form" novalidate>
          <!-- Step 1: Contact -->
          <div v-if="step === 1" class="form-panel">
            <h2 class="panel-title">Контактна інформація</h2>

            <div
              class="field"
              :class="{
                error: errors.name,
                success: touched.name && !errors.name,
              }"
            >
              <label for="name">Повне ім'я <span class="req">*</span></label>
              <input
                type="text"
                id="name"
                v-model="formData.name"
                @input="e => { formData.name = e.target.value.replace(/\d/g, ''); touch('name') }"
                @blur="touch('name')"
                placeholder="Іван Іваненко"
              />
              <span class="field-msg" v-if="errors.name">{{
                errors.name
              }}</span>
            </div>

            <div
              class="field"
              :class="{
                error: errors.phone,
                success: touched.phone && !errors.phone,
              }"
            >
              <label for="phone"
                >Номер телефону <span class="req">*</span></label
              >
              <input
                type="tel"
                id="phone"
                v-model="formData.phone"
                @input="formatPhone"
                @blur="touch('phone')"
                placeholder="380XXXXXXXXX"
                maxlength="12"
              />
              <span class="field-msg" v-if="errors.phone">{{
                errors.phone
              }}</span>
            </div>

            <div
              class="field"
              :class="{
                error: errors.email,
                success: touched.email && !errors.email,
              }"
            >
              <label for="email">Email адреса <span class="req">*</span></label>
              <input
                type="email"
                id="email"
                v-model="formData.email"
                @blur="touch('email')"
                placeholder="ivan@example.com"
              />
              <span class="field-msg" v-if="errors.email">{{
                errors.email
              }}</span>
            </div>

            <button type="submit" class="btn btn-primary btn-next">
              Далі — Адреса доставки →
            </button>
          </div>

          <!-- Step 2: Delivery -->
          <div v-if="step === 2" class="form-panel">
            <h2 class="panel-title">Адреса доставки</h2>

            <div
              class="field"
              :class="{
                error: errors.address,
                success: touched.address && !errors.address,
              }"
            >
              <label for="address"
                >Вулиця, будинок <span class="req">*</span></label
              >
              <input
                type="text"
                id="address"
                v-model="formData.address"
                @blur="touch('address')"
                placeholder="вул. Хрещатик, 1, кв. 5"
              />
              <span class="field-msg" v-if="errors.address">{{
                errors.address
              }}</span>
            </div>

            <div class="form-row">
              <div
                class="field"
                :class="{
                  error: errors.city,
                  success: touched.city && !errors.city,
                }"
              >
                <label for="city">Місто <span class="req">*</span></label>
                <input
                  type="text"
                  id="city"
                  v-model="formData.city"
                  @blur="touch('city')"
                  placeholder="Київ"
                />
                <span class="field-msg" v-if="errors.city">{{
                  errors.city
                }}</span>
              </div>
              <div class="field">
                <label for="state">Область / Регіон</label>
                <input
                  type="text"
                  id="state"
                  v-model="formData.state"
                  placeholder="Київська область"
                />
              </div>
            </div>

            <div class="form-row">
              <div class="field">
                <label for="zipCode">Поштовий індекс</label>
                <input
                  type="text"
                  id="zipCode"
                  v-model="formData.zipCode"
                  placeholder="01001"
                />
              </div>
              <div
                class="field"
                :class="{
                  error: errors.country,
                  success: touched.country && !errors.country,
                }"
              >
                <label for="country">Країна <span class="req">*</span></label>
                <input
                  type="text"
                  id="country"
                  v-model="formData.country"
                  @blur="touch('country')"
                  placeholder="Україна"
                />
                <span class="field-msg" v-if="errors.country">{{
                  errors.country
                }}</span>
              </div>
            </div>

            <div class="field">
              <label for="comment">Коментар до замовлення</label>
              <textarea
                id="comment"
                v-model="formData.comment"
                rows="2"
                placeholder="Особливі інструкції щодо доставки..."
              ></textarea>
            </div>

            <div class="step-actions">
              <button type="button" class="btn btn-outline" @click="step = 1">
                ← Назад
              </button>
              <button type="submit" class="btn btn-primary btn-next">
                Далі — Підтвердження →
              </button>
            </div>
          </div>

          <!-- Step 3: Confirm -->
          <div v-if="step === 3" class="form-panel">
            <h2 class="panel-title">Підтвердження замовлення</h2>

            <div class="confirm-block">
              <div class="confirm-section-label">Контакти</div>
              <div class="confirm-row">
                <span>Ім'я</span><span>{{ formData.name }}</span>
              </div>
              <div class="confirm-row">
                <span>Телефон</span><span>{{ formData.phone }}</span>
              </div>
              <div class="confirm-row">
                <span>Email</span><span>{{ formData.email }}</span>
              </div>
            </div>

            <div class="confirm-block">
              <div class="confirm-section-label">Доставка</div>
              <div class="confirm-row">
                <span>Адреса</span><span>{{ formData.address }}</span>
              </div>
              <div class="confirm-row">
                <span>Місто</span
                ><span
                  >{{ formData.city
                  }}{{ formData.state ? ", " + formData.state : "" }}</span
                >
              </div>
              <div class="confirm-row">
                <span>Країна</span
                ><span
                  >{{ formData.country
                  }}{{ formData.zipCode ? ", " + formData.zipCode : "" }}</span
                >
              </div>
              <div class="confirm-row" v-if="formData.comment">
                <span>Коментар</span><span>{{ formData.comment }}</span>
              </div>
            </div>

            <!-- Payment method selector -->
            <div class="confirm-block payment-method-block">
              <div class="confirm-section-label">Спосіб оплати</div>
              <label class="payment-option" :class="{ selected: paymentMethod === 'card' }">
                <input type="radio" value="card" v-model="paymentMethod" />
                <span class="payment-icon">💳</span>
                <span>Оплата карткою</span>
              </label>
              <label class="payment-option" :class="{ selected: paymentMethod === 'cash_on_delivery' }">
                <input type="radio" value="cash_on_delivery" v-model="paymentMethod" />
                <span class="payment-icon">🚚</span>
                <span>Оплата при отриманні</span>
              </label>
            </div>

            <!-- Card form (shown when card is selected) -->
            <div v-if="paymentMethod === 'card'" class="confirm-block card-form-block">
              <div class="confirm-section-label">Дані картки</div>

              <div class="field" :class="{ error: cardErrors.number, success: cardTouched.number && !cardErrors.number }">
                <label for="cardNumber">Номер картки <span class="req">*</span></label>
                <input
                  type="text"
                  id="cardNumber"
                  :value="cardData.number"
                  @input="onCardNumberInput"
                  @blur="touchCard('number')"
                  placeholder="1234 5678 9012 3456"
                  maxlength="19"
                  autocomplete="cc-number"
                />
                <span class="field-msg" v-if="cardErrors.number">{{ cardErrors.number }}</span>
              </div>

              <div class="field" :class="{ error: cardErrors.name, success: cardTouched.name && !cardErrors.name }">
                <label for="cardName">Ім'я власника <span class="req">*</span></label>
                <input
                  type="text"
                  id="cardName"
                  v-model="cardData.name"
                  @blur="touchCard('name')"
                  placeholder="IVAN IVANENKO"
                  autocomplete="cc-name"
                />
                <span class="field-msg" v-if="cardErrors.name">{{ cardErrors.name }}</span>
              </div>

              <div class="form-row">
                <div class="field" :class="{ error: cardErrors.expiry, success: cardTouched.expiry && !cardErrors.expiry }">
                  <label for="cardExpiry">Термін дії <span class="req">*</span></label>
                  <input
                    type="text"
                    id="cardExpiry"
                    :value="cardData.expiry"
                    @input="onExpiryInput"
                    @blur="touchCard('expiry')"
                    placeholder="MM/YY"
                    maxlength="5"
                    autocomplete="cc-exp"
                  />
                  <span class="field-msg" v-if="cardErrors.expiry">{{ cardErrors.expiry }}</span>
                </div>

                <div class="field" :class="{ error: cardErrors.cvv, success: cardTouched.cvv && !cardErrors.cvv }">
                  <label for="cardCvv">CVV <span class="req">*</span></label>
                  <input
                    type="password"
                    id="cardCvv"
                    v-model="cardData.cvv"
                    @blur="touchCard('cvv')"
                    placeholder="123"
                    maxlength="3"
                    autocomplete="cc-csc"
                  />
                  <span class="field-msg" v-if="cardErrors.cvv">{{ cardErrors.cvv }}</span>
                </div>
              </div>
            </div>

            <!-- Payment method in summary -->
            <div class="confirm-block">
              <div class="confirm-section-label">Підсумок оплати</div>
              <div class="confirm-row">
                <span>Спосіб оплати</span>
                <span>{{ paymentMethod === 'card' ? '💳 Картка' : '🚚 При отриманні' }}</span>
              </div>
            </div>

            <div v-if="orderError" class="alert alert-error mb-2">
              {{ orderError }}
            </div>

            <div class="step-actions">
              <button type="button" class="btn btn-outline" @click="step = 2">
                ← Назад
              </button>
              <button
                type="submit"
                class="btn btn-primary btn-next"
                :disabled="submitting"
              >
                {{
                  submitting
                    ? "Оформлення..."
                    : `Підтвердити — ₴${orderTotal.toFixed(2)}`
                }}
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Right: Order Summary -->
      <div class="checkout-right">
        <div class="summary-card">
          <div class="summary-header">
            <h2 class="summary-title">Ваше замовлення</h2>
            <span class="summary-count">{{ totalItems }} товар(ів)</span>
          </div>

          <div class="summary-items">
            <div v-for="item in cartItems" :key="item.id" class="summary-item">
              <div class="item-img-wrap">
                <img :src="item.image" :alt="item.name" class="item-img" />
                <span class="item-qty-badge">{{ item.quantity }}</span>
              </div>
              <div class="item-info">
                <p class="item-name">{{ item.name }}</p>
                <p class="item-price">
                  ₴{{ (item.price * item.quantity).toFixed(2) }}
                </p>
              </div>
            </div>
          </div>

          <div class="summary-divider"></div>

          <div class="summary-totals">
            <div class="total-line">
              <span>Підсумок</span>
              <span>₴{{ totalPrice.toFixed(2) }}</span>
            </div>
            <div class="total-line">
              <span>Доставка</span>
              <span class="free-shipping" v-if="shippingCost === 0"
                >Безкоштовно</span
              >
              <span v-else>₴{{ shippingCost.toFixed(2) }}</span>
            </div>
          </div>

          <div class="summary-divider"></div>

          <div class="total-grand">
            <span>Разом</span>
            <span>₴{{ orderTotal.toFixed(2) }}</span>
          </div>

          <div v-if="totalPrice < 500" class="shipping-hint">
            <span>🚚</span>
            Додайте ще ₴{{ (500 - totalPrice).toFixed(2) }} для безкоштовної
            доставки
          </div>
          <div v-else class="shipping-hint success">
            <span>✓</span> Безкоштовна доставка застосована
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from "vue";
import { useRouter } from "vue-router";
import { useCartStore, useUserStore } from "../store";
import { api } from "../services/api";

// --- Card validation helpers ---

function luhnCheck(cardNumber) {
  const digits = cardNumber.replace(/\D/g, "");
  if (digits.length !== 16) return false;
  let sum = 0;
  for (let i = 0; i < digits.length; i++) {
    let d = parseInt(digits[i]);
    if ((digits.length - 1 - i) % 2 === 1) {
      d *= 2;
      if (d > 9) d -= 9;
    }
    sum += d;
  }
  return sum % 10 === 0;
}

function validateExpiry(mmyy) {
  if (!mmyy || !mmyy.includes("/")) return "Невірний формат терміну дії";
  const [mm, yy] = mmyy.split("/");
  const month = parseInt(mm, 10);
  const year = 2000 + parseInt(yy, 10);
  if (isNaN(month) || isNaN(year)) return "Невірний формат терміну дії";
  if (month < 1 || month > 12) return "Невірний місяць";
  const now = new Date();
  const currentYear = now.getFullYear();
  const currentMonth = now.getMonth() + 1;
  if (year < currentYear || (year === currentYear && month < currentMonth))
    return "Термін дії картки минув";
  return null;
}

function validateCVV(cvv) {
  return /^\d{3}$/.test(cvv) ? null : "CVV повинен містити 3 цифри";
}

function formatCardNumber(value) {
  const digits = value.replace(/\D/g, "").slice(0, 16);
  return digits.replace(/(.{4})/g, "$1 ").trim();
}

// --------------------------------

export default {
  name: "CheckoutPage",
  setup() {
    const router = useRouter();
    const cartStore = useCartStore();
    const userStore = useUserStore();

    const user = userStore.currentUser || {};
    const step = ref(1);

    const rawPhone = (user.phone || '').replace(/\D/g, '');
    const formData = ref({
      name: user.name || "",
      phone: rawPhone || "380",
      email: user.email || "",
      address: user.address || "",
      city: "",
      state: "",
      zipCode: "",
      country: "Україна",
      comment: "",
    });

    const touched = ref({});
    const submitting = ref(false);
    const orderError = ref(null);

    // Payment method
    const paymentMethod = ref("cash_on_delivery");

    // Card data (never sent to backend)
    const cardData = ref({ number: "", name: "", expiry: "", cvv: "" });
    const cardTouched = ref({});

    const cardValidators = {
      number: (v) => {
        const digits = v.replace(/\D/g, "");
        if (digits.length !== 16) return "Номер картки повинен містити 16 цифр";
        if (!luhnCheck(v)) return "Невірний номер картки";
        return null;
      },
      name: (v) => (!v.trim() ? "Введіть ім'я власника картки" : null),
      expiry: (v) => validateExpiry(v),
      cvv: (v) => validateCVV(v),
    };

    const cardErrors = computed(() => {
      const result = {};
      for (const [field, validate] of Object.entries(cardValidators)) {
        if (cardTouched.value[field]) {
          const err = validate(cardData.value[field] || "");
          if (err) result[field] = err;
        }
      }
      return result;
    });

    const touchCard = (field) => {
      cardTouched.value = { ...cardTouched.value, [field]: true };
    };

    const onCardNumberInput = (e) => {
      cardData.value.number = formatCardNumber(e.target.value);
      touchCard("number");
    };

    const onExpiryInput = (e) => {
      let val = e.target.value.replace(/\D/g, "").slice(0, 4);
      if (val.length >= 3) val = val.slice(0, 2) + "/" + val.slice(2);
      cardData.value.expiry = val;
      touchCard("expiry");
    };

    const isCardValid = () => {
      // Touch all card fields to trigger validation display
      cardTouched.value = { number: true, name: true, expiry: true, cvv: true };
      return Object.values(cardValidators).every((validate, i) => {
        const field = Object.keys(cardValidators)[i];
        return !validate(cardData.value[field] || "");
      });
    };

    // Validation rules
    const validators = {
      name: (v) =>
        !v.trim()
          ? "Введіть ваше ім'я"
          : v.trim().length < 2
          ? "Мінімум 2 символи"
          : /\d/.test(v)
          ? "Ім'я не може містити цифри"
          : null,
      phone: (v) =>
        !v.trim()
          ? "Введіть номер телефону"
          : !/^380\d{9}$/.test(v.replace(/\D/g, ''))
          ? "Формат: 380XXXXXXXXX (12 цифр)"
          : null,
      email: (v) =>
        !v.trim()
          ? "Введіть email адресу"
          : !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)
          ? "Невірний формат email"
          : null,
      address: (v) => (!v.trim() ? "Введіть адресу доставки" : null),
      city: (v) => (!v.trim() ? "Введіть місто" : null),
      country: (v) => (!v.trim() ? "Введіть країну" : null),
    };

    const errors = computed(() => {
      const result = {};
      for (const [field, validate] of Object.entries(validators)) {
        if (touched.value[field]) {
          const err = validate(formData.value[field] || "");
          if (err) result[field] = err;
        }
      }
      return result;
    });

    const touch = (field) => {
      touched.value = { ...touched.value, [field]: true };
    };

    const formatPhone = (e) => {
      let digits = e.target.value.replace(/\D/g, '');
      if (!digits.startsWith('380')) {
        digits = '380' + digits.replace(/^380/, '');
      }
      if (digits.length > 12) digits = digits.slice(0, 12);
      formData.value.phone = digits;
      touch('phone');
    };

    const touchAll = (fields) => {
      const next = { ...touched.value };
      fields.forEach((f) => (next[f] = true));
      touched.value = next;
    };

    const validateStep = (fields) => {
      touchAll(fields);
      return fields.every((f) => {
        if (!validators[f]) return true;
        return !validators[f](formData.value[f] || "");
      });
    };

    const handleNext = async () => {
      if (step.value === 1) {
        if (validateStep(["name", "phone", "email"])) step.value = 2;
      } else if (step.value === 2) {
        if (validateStep(["address", "city", "country"])) step.value = 3;
      } else {
        await placeOrder();
      }
    };

    const cartItems = computed(() => cartStore.items);
    const totalItems = computed(() => cartStore.totalItems);
    const totalPrice = computed(() => cartStore.totalPrice);
    const shippingCost = computed(() => (totalPrice.value >= 500 ? 0 : 29.99));
    const orderTotal = computed(() => totalPrice.value + shippingCost.value);

    const placeOrder = async () => {
      // Validate card if payment by card selected
      if (paymentMethod.value === "card" && !isCardValid()) return;

      submitting.value = true;
      orderError.value = null;
      const order = {
        items: cartItems.value.map((item) => ({
          id: item.id,
          name: item.name,
          quantity: item.quantity,
          price: item.price,
        })),
        total: orderTotal.value,
        customer_info: { ...formData.value },
        payment_method: paymentMethod.value,
        payment_status: paymentMethod.value === 'card' ? 'paid' : 'pending',
        // cardData intentionally excluded
      };
      try {
        await api.orders.create(order);
        cartStore.clearCart();
        alert("Замовлення успішно оформлено! Дякуємо за покупку.");
        router.push("/profile");
      } catch (err) {
        orderError.value =
          err.response?.data?.message ||
          "Не вдалося оформити замовлення. Спробуйте ще раз.";
      } finally {
        submitting.value = false;
      }
    };

    return {
      step,
      formData,
      touched,
      errors,
      touch,
      formatPhone,
      handleNext,
      cartItems,
      totalItems,
      totalPrice,
      shippingCost,
      orderTotal,
      submitting,
      orderError,
      // payment
      paymentMethod,
      cardData,
      cardTouched,
      cardErrors,
      touchCard,
      onCardNumberInput,
      onExpiryInput,
      // card validation helpers (used in task 6)
      luhnCheck,
      validateExpiry,
      validateCVV,
      formatCardNumber,
    };
  },
};
</script>

<style scoped>
.checkout-page {
  padding: 1.5rem 0;
  min-height: calc(100vh - 80px);
}

.checkout-wrapper {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 2rem;
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 1.5rem;
  align-items: start;
}

/* ---- Left side ---- */
.checkout-left {
  min-width: 0;
}

.checkout-title {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  color: var(--text-primary);
}

/* Steps */
.steps {
  display: flex;
  align-items: center;
  margin-bottom: 1.75rem;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
}

.step-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 2px solid var(--border-color);
  background: var(--bg-primary);
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.875rem;
  transition: var(--transition);
}

.step.active .step-circle {
  border-color: var(--primary-color);
  background: var(--primary-color);
  color: white;
}

.step.done .step-circle {
  border-color: var(--success-color);
  background: var(--success-color);
  color: white;
}

.step-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
  font-weight: 500;
  white-space: nowrap;
}

.step.active .step-label {
  color: var(--primary-color);
  font-weight: 600;
}

.step-line {
  flex: 1;
  height: 2px;
  background: var(--border-color);
  margin: 0 0.5rem;
  margin-bottom: 1.1rem;
  transition: var(--transition);
}

.step-line.active {
  background: var(--success-color);
}

/* Form panel */
.form-panel {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  padding: 1.75rem;
  box-shadow: var(--shadow-md);
}

.panel-title {
  font-size: 1.125rem;
  font-weight: 700;
  margin-bottom: 1.25rem;
  color: var(--text-primary);
}

/* Fields */
.field {
  margin-bottom: 1.1rem;
  position: relative;
}

.field label {
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 0.35rem;
  color: var(--text-primary);
}

.req {
  color: var(--danger-color);
  margin-left: 2px;
}

.field input,
.field textarea {
  width: 100%;
  padding: 0.65rem 0.875rem;
  font-size: 0.9375rem;
  border: 1.5px solid var(--border-color);
  border-radius: var(--radius-md);
  background: var(--bg-primary);
  color: var(--text-primary);
  transition: border-color 0.2s, box-shadow 0.2s;
}

.field input:focus,
.field textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.field.error input,
.field.error textarea {
  border-color: var(--danger-color);
  background: #fff8f8;
}

.field.error input:focus,
.field.error textarea:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.field.success input {
  border-color: var(--success-color);
}

.field-msg {
  display: block;
  font-size: 0.78rem;
  color: var(--danger-color);
  margin-top: 0.3rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

/* Buttons */
.btn-next {
  width: 100%;
  margin-top: 0.5rem;
  padding: 0.8rem;
  font-size: 1rem;
}

.step-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.step-actions .btn-outline {
  flex: 0 0 auto;
  padding: 0.8rem 1.25rem;
}

.step-actions .btn-next {
  flex: 1;
  margin-top: 0;
}

/* Confirm block */
.confirm-block {
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  padding: 1rem;
  margin-bottom: 1rem;
}

.confirm-section-label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-secondary);
  margin-bottom: 0.6rem;
}

.confirm-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
  padding: 0.3rem 0;
  gap: 1rem;
}

.confirm-row span:first-child {
  color: var(--text-secondary);
  flex-shrink: 0;
}

.confirm-row span:last-child {
  color: var(--text-primary);
  font-weight: 500;
  text-align: right;
}

/* ---- Right side ---- */
.checkout-right {
  position: sticky;
  top: 90px;
}

.summary-card {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  padding: 1.5rem;
}

.summary-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}

.summary-title {
  font-size: 1.125rem;
  font-weight: 700;
  margin: 0;
  color: var(--text-primary);
}

.summary-count {
  font-size: 0.8rem;
  color: var(--text-secondary);
  background: var(--bg-tertiary);
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
}

.summary-items {
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
  max-height: 260px;
  overflow-y: auto;
  padding-right: 2px;
}

.summary-item {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.item-img-wrap {
  position: relative;
  flex-shrink: 0;
}

.item-img {
  width: 52px;
  height: 52px;
  object-fit: cover;
  border-radius: var(--radius-md);
  background: var(--bg-tertiary);
}

.item-qty-badge {
  position: absolute;
  top: -6px;
  right: -6px;
  background: var(--primary-color);
  color: white;
  font-size: 0.7rem;
  font-weight: 700;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.item-info {
  flex: 1;
  min-width: 0;
}

.item-name {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.2rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-price {
  font-size: 0.8125rem;
  color: var(--primary-color);
  font-weight: 600;
  margin: 0;
}

.summary-divider {
  height: 1px;
  background: var(--border-color);
  margin: 1rem 0;
}

.summary-totals {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.total-line {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.free-shipping {
  color: var(--success-color);
  font-weight: 600;
}

.total-grand {
  display: flex;
  justify-content: space-between;
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-top: 0.25rem;
}

.shipping-hint {
  margin-top: 1rem;
  font-size: 0.8rem;
  color: var(--text-secondary);
  background: var(--bg-tertiary);
  border-radius: var(--radius-md);
  padding: 0.6rem 0.875rem;
  display: flex;
  gap: 0.4rem;
  align-items: flex-start;
}

.shipping-hint.success {
  background: #d1fae5;
  color: #065f46;
}

/* Responsive */
@media (max-width: 900px) {
  .checkout-wrapper {
    grid-template-columns: 1fr;
  }

  .checkout-right {
    position: static;
    order: -1;
  }

  .summary-items {
    max-height: 180px;
  }
}

/* Payment method selector */
.payment-method-block {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.payment-option {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.65rem 0.875rem;
  border: 1.5px solid var(--border-color);
  border-radius: var(--radius-md);
  cursor: pointer;
  font-size: 0.9375rem;
  transition: border-color 0.2s, background 0.2s;
  background: var(--bg-primary);
}

.payment-option input[type="radio"] {
  width: auto;
  accent-color: var(--primary-color);
}

.payment-option.selected {
  border-color: var(--primary-color);
  background: rgba(37, 99, 235, 0.04);
}

.payment-icon {
  font-size: 1.1rem;
}

/* Card form */
.card-form-block {
  margin-top: 0;
}

@media (max-width: 600px) {
  .form-row {
    grid-template-columns: 1fr;
  }

  .form-panel {
    padding: 1.25rem;
  }

  .checkout-title {
    font-size: 1.375rem;
  }
}
</style>
