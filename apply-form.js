document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("applyForm");
  const successBox = document.getElementById("applySuccess");
  const messageEl = document.getElementById("applyMessage");
  const applyAnotherBtn = document.getElementById("applyAnother");
  const submitBtn = form.querySelector('button[type="submit"]');

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    // clear old error states
    form.querySelectorAll(".field-error").forEach(el => el.classList.add("hidden"));
    messageEl.classList.add("hidden");

    // basic required-field check (uses your existing markup)
    let hasError = false;
    form.querySelectorAll("[required]").forEach(field => {
      if (!field.value.trim()) {
        hasError = true;
        const errorSpan = field.parentElement.querySelector(".field-error");
        if (errorSpan) errorSpan.classList.remove("hidden");
      }
    });
    if (hasError) return;

    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = "Submitting...";

    try {
      const formData = new FormData(form);
      const res = await fetch("send-mail.php", {
        method: "POST",
        body: formData
      });
      const data = await res.json();

      if (data.success) {
        form.classList.add("hidden");
        successBox.classList.remove("hidden");
      } else {
        messageEl.textContent = data.message || "Something went wrong.";
        messageEl.classList.remove("hidden", "text-green-600");
        messageEl.classList.add("text-red-600");
      }
    } catch (err) {
      console.error(err);
      messageEl.textContent = "Network error. Please try again.";
      messageEl.classList.remove("hidden", "text-green-600");
      messageEl.classList.add("text-red-600");
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  });

  if (applyAnotherBtn) {
    applyAnotherBtn.addEventListener("click", function () {
      form.reset();
      form.classList.remove("hidden");
      successBox.classList.add("hidden");
    });
  }
});
