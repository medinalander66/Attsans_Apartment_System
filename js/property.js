export function loadRoomDetails() {
    const params = new URLSearchParams(window.location.search);
    const roomId = params.get("id");
    if (!roomId || isNaN(roomId)) return;

    fetch(`backend.php?action=get_room&id=${roomId}`)
        .then(response => response.json())
        .then(room => {
            if (room.error) {
                document.querySelector("main").innerHTML = `<p>${room.error}</p>`;
                return;
            }

            document.querySelector(".inquire-button").onclick = () => {
                window.location.href = `InquireLoggedIn.html?id=${roomId}`;
            };
            document.querySelector("h2").textContent = `Room ${room.room_number}`;
            document.querySelector(".room-image").innerHTML = `<img src="Images/rooms/${room.room_image}" alt="Room Image">`;
            const ddTags = document.querySelectorAll("dd");
            ddTags[0].textContent = room.room_capacity;
            ddTags[1].textContent = room.room_type;
            ddTags[2].textContent = `₱${room.room_rate}`;
            document.querySelector(".description p").textContent = room.room_description;
        })
        .catch(() => {
            document.querySelector("main").innerHTML = "<p>Failed to load room data.</p>";
        });
}

export function loadRandomRooms() {
    fetch("backend.php?action=get_random_rooms")
        .then(res => res.json())
        .then(rooms => {
            if (rooms.error) return;
            const cards = document.querySelectorAll(".recent-properties-card");
            rooms.forEach((room, i) => {
                if (!cards[i]) return;
                cards[i].querySelector("h3").textContent = `Room ${room.room_number}`;
                cards[i].querySelectorAll("p")[0].textContent = `Capacity: ${room.room_capacity}`;
                cards[i].querySelectorAll("p")[1].textContent = `Type: ${room.room_type}`;
                cards[i].querySelectorAll("p")[2].textContent = `Rate: ₱${room.room_rate}`;
                cards[i].querySelector(".recent-properties-image-placeholder").innerHTML =
                    `<img src="Images/rooms/${room.room_image}" alt="Room Image">`;
                cards[i].querySelector(".recent-properties-button").href = `propertyPage.html?id=${room.property_id}`;
            });
        });
}

export function loadInquireRoomDetails() {
    // First: Load user info
    const firstNameInput = document.getElementById('first_name');
    if (firstNameInput) {
        fetch('backend.php?action=get_user_info')
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    window.location.href = "index.html";
                } else {
                    firstNameInput.value = data.first_name;
                    document.getElementById('last_name').value = data.last_name;
                    document.getElementById('email').value = data.email;
                    document.getElementById('phone_number').value = data.phone_number;
                }
            })
            .catch(err => console.error('Error fetching user data:', err));
    }

    // Second: Load room info
    const params = new URLSearchParams(window.location.search);
    const roomId = params.get("id");
    if (roomId && !isNaN(roomId)) {
        fetch(`backend.php?action=get_room&id=${roomId}`)
            .then(response => response.json())
            .then(room => {
                if (room.error) {
                    document.querySelector("main").innerHTML = `<p>${room.error}</p>`;
                    return;
                }

                document.querySelector(".room-image").innerHTML =
                    `<img src="Images/rooms/${room.room_image}" alt="Room Image">`;

                document.querySelector(".room_number").textContent = room.room_number;
                document.querySelector(".room_capacity").textContent = room.room_capacity;
                document.querySelector(".room_type").textContent = room.room_type;
                document.querySelector(".room_rate").textContent = `₱${room.room_rate}`;
            })
            .catch(() => {
                document.querySelector("main").innerHTML = "<p>Failed to load room data.</p>";
            });
    }
}




