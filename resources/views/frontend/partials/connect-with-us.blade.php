@php
  $contactHeadingKey = $contactHeadingKey ?? 'contact_heading';
  $contactSubtextKey = $contactSubtextKey ?? 'contact_subtext';
  $contactBgValue = $contactBgValue ?? '';
  $contactPageSlug = $contactPageSlug ?? 'nordic';
  $submitLabelRaw = trim((string) $cv('contact_form_submit', 'Send Message'));
  $submitLabelBase = preg_replace('/\s*(?:(?:->|=>|→)\s*)+$/u', '', $submitLabelRaw);
  $submitLabelBase = trim((string) $submitLabelBase);
  if ($submitLabelBase === '') {
    $submitLabelBase = 'Send Message';
  }
@endphp

<section
  id="connect-with-us"
  class="shared-connect"
  @if($contactBgValue !== '')
    style="--shared-connect-bg: {{ $contactBgValue }};"
  @endif
>
  <div class="shared-connect-inner">
    <div class="shared-connect-head">
      <h2 class="shared-connect-title" data-ve-field="{{ $contactHeadingKey }}">{{ $cv($contactHeadingKey, 'CONNECT WITH US!') }}</h2>
      <p class="shared-connect-subtext" data-ve-field="{{ $contactSubtextKey }}">{{ $contactSubtext ?? $cv($contactSubtextKey, "Hungry for answers? Let's cook something up together - send us a bite of your thoughts!") }}</p>
    </div>

    <form
      id="sharedConnectForm"
      class="shared-connect-form"
      method="POST"
      action="{{ route('contact.send') }}"
      data-submit-url="{{ route('contact.send') }}"
      data-page-slug="{{ $contactPageSlug }}"
    >
      @csrf
      <input type="hidden" name="page_slug" value="{{ $contactPageSlug }}">
      <div class="shared-connect-grid">
        <div class="shared-connect-field">
          <label class="shared-connect-label" data-ve-field="contact_form_company_label">{{ $cv('contact_form_company_label', 'Company Name') }}</label>
          <input type="text" name="company" class="shared-connect-input" placeholder="{{ $cv('contact_form_company_ph', 'e.g. Acme Corp') }}" required />
        </div>

        <div class="shared-connect-field">
          <label class="shared-connect-label" data-ve-field="contact_form_industry_label">{{ $cv('contact_form_industry_label', 'Industry') }}</label>
          <input type="text" name="industry" class="shared-connect-input" placeholder="{{ $cv('contact_form_industry_ph', 'e.g. Food & Beverage') }}" />
        </div>

        <div class="shared-connect-field">
          <label class="shared-connect-label" data-ve-field="contact_form_name_label">{{ $cv('contact_form_name_label', 'Full Name') }}</label>
          <input type="text" name="name" class="shared-connect-input" placeholder="{{ $cv('contact_form_name_ph', 'Your name') }}" required />
        </div>

        <div class="shared-connect-field">
          <label class="shared-connect-label" data-ve-field="contact_form_email_label">{{ $cv('contact_form_email_label', 'Email Address') }}</label>
          <input type="email" name="email" class="shared-connect-input" placeholder="{{ $cv('contact_form_email_ph', 'you@company.com') }}" required />
        </div>

        <div class="shared-connect-field shared-connect-field--full">
          <label class="shared-connect-label" data-ve-field="contact_form_message_label">{{ $cv('contact_form_message_label', 'Message') }}</label>
          <textarea name="message" class="shared-connect-input" rows="4" style="resize:none;" placeholder="{{ $cv('contact_form_message_ph', 'Tell us about your inquiry...') }}" required></textarea>
        </div>
      </div>

      <div class="shared-connect-actions">
        <button type="submit" class="shared-connect-submit" data-ve-field="contact_form_submit">{{ $submitLabelBase }} -&gt;</button>
      </div>
    </form>

    <div id="sharedConnectSuccess" class="shared-connect-success" data-ve-field="contact_form_success">
      {{ $cv('contact_form_success', "Thank you! We'll be in touch soon.") }}
    </div>
    <div id="sharedConnectError" class="shared-connect-error" role="alert">
      Something went wrong. Please try again.
    </div>
  </div>
</section>

<script>
  (function () {
    let activeInquiryToast = null;
    const showInquiryToast = function (message, type, options) {
      type = type || 'success';
      options = options || {};
      const persist = !!options.persist;
      const id = 'inquiry-toast-style';
      let style = document.getElementById(id);
      if (!style) {
        style = document.createElement('style');
        style.id = id;
        document.head.appendChild(style);
      }
      style.textContent = `
          .inquiry-toast{position:fixed;left:50%;top:50%;transform:translate(-50%,-50%) scale(.94);z-index:9999;min-width:320px;max-width:min(92vw,560px);padding:26px 28px;border-radius:20px;font-family:var(--font-body,"Fira Sans",Arial,sans-serif);font-size:16px;font-weight:600;line-height:1.5;box-shadow:none;opacity:0;pointer-events:none;transition:opacity .42s ease,transform .58s cubic-bezier(.2,.85,.28,1);text-align:center}
          .inquiry-toast.is-show{opacity:1;transform:translate(-50%,-50%) scale(1)}
          .inquiry-toast--success{background:linear-gradient(180deg,#ffffff,#f7fff8);color:#1d8f3a;border:1px solid rgba(29,143,58,.25)}
          .inquiry-toast--loading{background:#ffffff;color:#1d8f3a;border:1px solid rgba(29,143,58,.25)}
          .inquiry-toast--error{background:#ffffff;color:#7f1d1d;border:1px solid rgba(127,29,29,.22)}
          .inquiry-toast__stack{display:flex;flex-direction:column;align-items:center;gap:14px}
          .inquiry-toast__message{color:#1d8f3a;font-weight:700}
          .inquiry-toast__icon{width:66px;height:66px;flex:0 0 66px;color:#20a54a;filter:none}
          .inquiry-toast__icon circle,.inquiry-toast__icon path{transform-box:fill-box;transform-origin:center}
          .inquiry-toast__icon circle{fill:none;stroke:currentColor;stroke-width:2.4;opacity:.92;stroke-dasharray:58;stroke-dashoffset:58;animation:inquiry-ring 1.18s cubic-bezier(.16,1,.3,1) forwards}
          .inquiry-toast__icon path{fill:none;stroke:currentColor;stroke-width:3.3;stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:22;stroke-dashoffset:22;animation:inquiry-check .92s .78s cubic-bezier(.2,.9,.3,1) forwards}
          .inquiry-toast__loading-icon{width:62px;height:62px;flex:0 0 62px;color:#20a54a;animation:inquiry-spin 1.3s linear infinite}
          .inquiry-toast__loading-icon circle{fill:none;stroke:currentColor;stroke-width:2.8;stroke-linecap:round;stroke-dasharray:42 18;opacity:.95}
          .inquiry-toast--error .inquiry-toast__icon circle,.inquiry-toast--error .inquiry-toast__icon path{animation:none;transform:none;stroke-dasharray:none;stroke-dashoffset:0;opacity:.85}
          @keyframes inquiry-ring{0%{stroke-dashoffset:58;opacity:.25;transform:scale(.84)}65%{opacity:1}100%{stroke-dashoffset:0;opacity:1;transform:scale(1)}}
          @keyframes inquiry-check{0%{stroke-dashoffset:22;opacity:.35}100%{stroke-dashoffset:0;opacity:1}}
          @keyframes inquiry-spin{to{transform:rotate(360deg)}}
        `;
      if (activeInquiryToast && activeInquiryToast.parentNode) {
        activeInquiryToast.remove();
        activeInquiryToast = null;
      }
      const toast = document.createElement('div');
      toast.className = 'inquiry-toast ' + (type === 'error' ? 'inquiry-toast--error' : 'inquiry-toast--success');
      if (type === 'success') {
        toast.innerHTML = `
          <div class="inquiry-toast__stack">
            <span class="inquiry-toast__message">${String(message).replace(/[&<>"]/g, function (c) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c]; })}</span>
            <svg class="inquiry-toast__icon" viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="12" cy="12" r="9.2"></circle>
              <path d="M7.7 12.6l2.9 2.9 5.8-6.1"></path>
            </svg>
          </div>
        `;
      } else if (type === 'loading') {
        toast.className = 'inquiry-toast inquiry-toast--loading';
        toast.innerHTML = `
          <div class="inquiry-toast__stack">
            <span class="inquiry-toast__message">${String(message).replace(/[&<>"]/g, function (c) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c]; })}</span>
            <svg class="inquiry-toast__loading-icon" viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="12" cy="12" r="9"></circle>
            </svg>
          </div>
        `;
      } else {
        toast.textContent = message;
      }
      document.body.appendChild(toast);
      requestAnimationFrame(() => toast.classList.add('is-show'));
      const closeToast = function () {
        toast.classList.remove('is-show');
        setTimeout(() => {
          if (toast.parentNode) toast.remove();
        }, 320);
      };
      if (persist) {
        activeInquiryToast = toast;
      } else {
        setTimeout(closeToast, 3600);
      }
      return {
        close: function () {
          if (activeInquiryToast === toast) activeInquiryToast = null;
          closeToast();
        },
      };
    };

    const form = document.getElementById('sharedConnectForm');
    const success = document.getElementById('sharedConnectSuccess');
    const error = document.getElementById('sharedConnectError');
    const isVisualEditor = document.body?.dataset?.isVisualEditor === '1';
    if (!form || !success) return;

    success.classList.remove('is-visible');
    if (error) error.classList.remove('is-visible');

    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      success.classList.remove('is-visible');
      if (error) error.classList.remove('is-visible');

      if (isVisualEditor) {
        success.classList.remove('is-visible');
        form.reset();
        showInquiryToast(success.textContent || "Thank you! We'll be in touch soon.", 'success');
        return;
      }

      const submitBtn = form.querySelector('button[type="submit"]');
      const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
      let sendingToast = null;

      try {
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = 'Sending...';
        }
        sendingToast = showInquiryToast('Sending your message...', 'loading', { persist: true });

        const formData = new FormData(form);
        if (!formData.get('page_slug')) {
          formData.append('page_slug', form.dataset.pageSlug || 'nordic');
        }

        const response = await fetch(form.dataset.submitUrl || form.action || '/contact/send', {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
          },
          body: formData,
        });

        const payload = await response.json().catch(() => ({}));
        if (!response.ok || !payload.success) {
          throw new Error(payload.message || 'Could not send your message.');
        }

        if (payload.message) {
          success.textContent = payload.message;
        }
        success.classList.remove('is-visible');
        if (sendingToast && typeof sendingToast.close === 'function') sendingToast.close();
        const toastType = payload.mail_status && payload.mail_status !== 'sent' ? 'error' : 'success';
        showInquiryToast(success.textContent || "Thank you! We'll be in touch soon.", toastType);
        form.reset();
      } catch (err) {
        if (sendingToast && typeof sendingToast.close === 'function') sendingToast.close();
        if (error) {
          error.textContent = err?.message || 'Something went wrong. Please try again.';
          error.classList.remove('is-visible');
          showInquiryToast(error.textContent, 'error');
        }
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnHtml;
        }
      }
    });
  })();
</script>
