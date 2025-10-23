const messages = new Map([
    [1, new Map([
        [1, { from: 101, text: "¿A qué hora nos encontramos exactamente?" }],
        [2, { from: 100, text: "En la plaza, 15 minutos antes por favor." }],
        [3, { from: 104, text: "¡Perfecto! Llevaré agua." }]
    ])],
    [2, new Map([
        [1, { from: 105, text: "¿Se puede llevar casco propio?" }],
        [2, { from: 104, text: "Sí, incluso es recomendable." }]
    ])],
    [3, new Map([
        [1, { from: 107, text: "¡Qué emoción este viaje a la luna!" }],
        [2, { from: 108, text: "Recuerden traer sus trajes espaciales 🚀" }]
    ])]
]);

export default messages;
