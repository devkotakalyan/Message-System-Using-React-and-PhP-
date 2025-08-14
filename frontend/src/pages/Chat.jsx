import { useEffect, useMemo, useRef, useState } from "react";
import { useParams } from "react-router-dom";
import { useAuth } from "../auth/AuthContext.jsx";

export default function Chat() {
  const { peerId: peerIdParam } = useParams();
  const peerId = parseInt(peerIdParam, 10);
  const { api, user } = useAuth();
  const [messages, setMessages] = useState([]);
  const [input, setInput] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const endRef = useRef(null);

  async function load() {
    if (!peerId) return;
    setLoading(true);
    setError("");
    try {
      const res = await api.messagesList(peerId);
      setMessages(res.messages || []);
    } catch (e) {
      setError(e.message || "Failed to load messages");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
  }, [peerId]);
  useEffect(() => {
    endRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  async function send() {
    const content = input.trim();
    if (!content) return;
    setError("");
    try {
      await api.sendMessage(peerId, content);
      setInput("");
      await load();
    } catch (e) {
      setError(e.message || "Failed to send");
    }
  }

  async function beginEdit(message) {
    const updated = prompt("Edit message:", message.content);
    if (updated == null) return;
    try {
      await api.editMessage(message.id, updated);
      await load();
    } catch (e) {
      setError(e.message || "Failed to edit");
    }
  }

  async function remove(message) {
    if (!confirm("Delete this message?")) return;
    try {
      await api.deleteMessage(message.id);
      await load();
    } catch (e) {
      setError(e.message || "Failed to delete");
    }
  }

  const myId = user?.id;

  return (
    <div className="chat">
      <div className="chat-body">
        {loading && <div className="muted">Loading…</div>}
        {messages.map((m) => (
          <div
            key={m.id}
            className={`bubble ${m.sender_id === myId ? "me" : "them"}`}
          >
            <div className="content">
              {m.is_deleted ? (
                <i className="muted">Message deleted</i>
              ) : (
                m.content
              )}
            </div>
            <div className="meta">
              <span>{new Date(m.created_at).toLocaleString()}</span>
              {m.sender_id === myId && !m.is_deleted && (
                <>
                  <button className="link" onClick={() => beginEdit(m)}>
                    Edit
                  </button>
                  <button className="link" onClick={() => remove(m)}>
                    Delete
                  </button>
                </>
              )}
            </div>
          </div>
        ))}
        <div ref={endRef} />
      </div>
      {error && <div className="error">{error}</div>}
      <div className="chat-input">
        <input
          placeholder="Type a message"
          value={input}
          onChange={(e) => setInput(e.target.value)}
          onKeyDown={(e) => (e.key === "Enter" ? send() : null)}
        />
        <button className="btn" onClick={send}>
          Send
        </button>
      </div>
    </div>
  );
}
