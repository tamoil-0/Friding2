const events = new Map([
    [1, {
        title: "Caminata al Cerro San Cristóbal",
        description: "Una subida para disfrutar del aire libre...",
        meetingPoint: "Plaza Mayor, Lima - frente a la Catedral",
        date: "20 de agosto del 2025",
        time: "8:30 AM",
        cost: "S/ 10",
        createdBy: 100,
        joined: false,
        participants: [101, 106, 104],
        requests: [103, 102]
    }],
    [2, {
        title: "Paseo en Bicicleta",
        description: "Recorrido por la Costa Verde",
        meetingPoint: "Parque Kennedy, Miraflores",
        date: "25 de agosto del 2025",
        time: "7:00 AM",
        cost: "S/ 5",
        createdBy: 104,
        joined: false,
        participants: [104, 105],
        requests: [104]
    }],
    [3, {
        title: "Viaje a la luna1",
        description: "relleno",
        meetingPoint: "Parque Kennedy, Miraflores",
        date: "26 de agosto del 2025",
        time: "9:00 AM",
        cost: "S/ 100",
        createdBy: 100,
        joined: false,
        participants: [107, 108],
        requests: [102]
    }],
    [4, {
        title: "Viaje a la luna2",
        description: "relleno",
        meetingPoint: "Parque Kennedy, Miraflores",
        date: "26 de agosto del 2025",
        time: "9:00 AM",
        cost: "S/ 100",
        createdBy: 106, 
        joined: true,
        participants: [107, 103],
        requests: []
    }],
    [5, {
        title: "Viaje a la luna3",
        description: "relleno",
        meetingPoint: "Parque Kennedy, Miraflores",
        date: "26 de agosto del 2025",
        time: "9:00 AM",
        cost: "S/ 100",
        createdBy: 103,
        joined: false,
        participants: [107, 106],
        requests: []
    }],
    [6, {
        title: "Viaje a la luna4",
        description: "relleno",
        meetingPoint: "Parque Kennedy, Miraflores",
        date: "26 de agosto del 2025",
        time: "9:00 AM",
        cost: "S/ 100",
        createdBy: 102,
        joined: true,
        participants: [104, 103],
        requests: [102]
    }]
]);

export default events;