const widget = document.getElementById("chat-widget");
const log = document.getElementById("chat-log");
const input = document.getElementById("chat-input");
const sendBtn = document.getElementById("chat-send");
const status = document.getElementById("chat-status");

if (widget && log && input && sendBtn && status) {
  const links = {
    booking: widget.dataset.booking || "",
    bookingFull: widget.dataset.bookingFull || "",
    zoom: widget.dataset.zoom || "",
    whatsapp: widget.dataset.whatsapp || ""
  };

  const addMessage = (role, text) => {
    const div = document.createElement("div");
    div.className = `chat-message ${role}`;
    div.textContent = text;
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;
  };

  const sendMessage = async () => {
    const message = input.value.trim();
    if (!message) {
      return;
    }
    addMessage("user", message);
    input.value = "";
    status.textContent = "Sending...";
    sendBtn.disabled = true;

    try {
      const response = await fetch("chat_endpoint.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({ message, links })
      });

      if (!response.ok) {
        throw new Error("Request failed");
      }

      const data = await response.json();
      if (data.reply) {
        addMessage("assistant", data.reply);
      } else {
        addMessage("assistant", "Sorry, I had trouble answering. Please call or WhatsApp 07729 689420.");
      }
    } catch (error) {
      addMessage("assistant", "Sorry, I had trouble answering. Please call or WhatsApp 07729 689420.");
    } finally {
      status.textContent = "No quotes for big jobs. Booking help only.";
      sendBtn.disabled = false;
    }
  };

  sendBtn.addEventListener("click", sendMessage);
  input.addEventListener("keydown", (event) => {
    if (event.key === "Enter" && !event.shiftKey) {
      event.preventDefault();
      sendMessage();
    }
  });
}
