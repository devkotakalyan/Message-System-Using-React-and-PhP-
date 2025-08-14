const BASE_URL = "http://localhost/Message_system/api";

export function makeApi(token) {
  async function request(path, { method = "GET", body, headers } = {}) {
    const res = await fetch(`${BASE_URL}${path}`, {
      method,
      headers: {
        "Content-Type": "application/json",
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...(headers || {}),
      },
      body: body ? JSON.stringify(body) : undefined,
    });
    const contentType = res.headers.get("Content-Type") || "";
    const isJson = contentType.includes("application/json");
    const data = isJson ? await res.json() : await res.text();
    if (!res.ok) {
      const errorMessage =
        isJson && data && data.error ? data.error : res.statusText;
      throw new Error(errorMessage);
    }
    return data;
  }

  return {
    register: (name, email, password) =>
      request("/auth/register", {
        method: "POST",
        body: { name, email, password },
      }),
    login: (email, password) =>
      request("/auth/login", { method: "POST", body: { email, password } }),

    friendsList: () => request("/friends/list"),
    sendFriendRequest: (toUserId) =>
      request("/friends/request", { method: "POST", body: { toUserId } }),
    respondFriendRequest: (requestId, action) =>
      request("/friends/respond", {
        method: "POST",
        body: { requestId, action },
      }),

    messagesList: (peerId) =>
      request(`/messages/list?peerId=${encodeURIComponent(peerId)}`),
    sendMessage: (toUserId, content) =>
      request("/messages/send", {
        method: "POST",
        body: { toUserId, content },
      }),
    editMessage: (messageId, content) =>
      request("/messages/edit", {
        method: "PATCH",
        body: { messageId, content },
      }),
    deleteMessage: (messageId) =>
      request(`/messages/delete?messageId=${encodeURIComponent(messageId)}`, {
        method: "DELETE",
      }),
  };
}
