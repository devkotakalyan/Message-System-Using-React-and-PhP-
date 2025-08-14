import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { useAuth } from "../auth/AuthContext.jsx";

export default function Friends() {
  const { api } = useAuth();
  const [friends, setFriends] = useState([]);
  const [pending, setPending] = useState([]);
  const [loadError, setLoadError] = useState("");
  const [targetUserId, setTargetUserId] = useState("");
  const [actionError, setActionError] = useState("");

  async function load() {
    setLoadError("");
    try {
      const data = await api.friendsList();
      setFriends(data.friends || []);
      setPending(data.pending || []);
    } catch (e) {
      setLoadError(e.message || "Failed to load");
    }
  }

  useEffect(() => {
    load();
  }, []);

  async function sendRequest() {
    setActionError("");
    const id = parseInt(targetUserId, 10);
    if (!id) {
      setActionError("Enter a valid numeric user id");
      return;
    }
    try {
      await api.sendFriendRequest(id);
      setTargetUserId("");
      await load();
    } catch (e) {
      setActionError(e.message || "Failed to send request");
    }
  }

  async function respond(requestId, action) {
    setActionError("");
    try {
      await api.respondFriendRequest(requestId, action);
      await load();
    } catch (e) {
      setActionError(e.message || "Failed to respond");
    }
  }

  return (
    <div className="grid">
      <section className="card">
        <h2>Your friends</h2>
        {loadError && <div className="error">{loadError}</div>}
        {friends.length === 0 ? (
          <div className="muted">No friends yet</div>
        ) : (
          <ul className="list">
            {friends.map((f) => (
              <li key={f.id} className="list-item">
                <div>
                  <div className="title">{f.name}</div>
                  <div className="muted">{f.email}</div>
                </div>
                <Link className="btn" to={`/chat/${f.id}`}>
                  Message
                </Link>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="card">
        <h2>Pending requests</h2>
        {pending.length === 0 ? (
          <div className="muted">No pending requests</div>
        ) : (
          <ul className="list">
            {pending.map((p) => (
              <li key={p.requestId} className="list-item">
                <div>
                  <div className="title">{p.name}</div>
                  <div className="muted">{p.email}</div>
                </div>
                <div className="actions">
                  <button
                    className="btn"
                    onClick={() => respond(p.requestId, "accept")}
                  >
                    Accept
                  </button>
                  <button
                    className="btn secondary"
                    onClick={() => respond(p.requestId, "decline")}
                  >
                    Decline
                  </button>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="card">
        <h2>Send friend request</h2>
        <div className="row">
          <input
            placeholder="Target user id"
            value={targetUserId}
            onChange={(e) => setTargetUserId(e.target.value)}
          />
          <button className="btn" onClick={sendRequest}>
            Send
          </button>
        </div>
        {actionError && <div className="error">{actionError}</div>}
        <div className="muted">
          Note: backend currently supports sending by numeric user id.
        </div>
      </section>
    </div>
  );
}
