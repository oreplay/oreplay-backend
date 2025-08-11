// Run with:
// k6 run load-tests.js
import http from 'k6/http'
import { check, sleep } from 'k6'

export let options = {
    vus: 700, // virtual users
    duration: '15s',
}

const isStatus200 = (url, tagName) => {
    let res = http.get(url, { tags: { name: tagName } })
    check(res, { 'Response is OK': (r) => r.status === 200 })
    return res
}

export default function () {
    // 2 parallel requests
    isStatus200('https://www.oreplay.es/api/v1/', 'root')
    let res2 = isStatus200('https://www.oreplay.es/api/v1/events', 'events')

    // Request a specific event
    if (res2.status === 200) {
        let events = res2.json()
        if (events.length > 0) {
            let eventId = events[0].id
            isStatus200(`https://www.oreplay.es/api/v1/events/${eventId}`, 'event-detail')
            eventId = '5183875c-f33f-43d7-842c-1b26fe50d47d'
            let stageId = '470ada23-4825-48ce-b087-aa59b2db7577'
            isStatus200(`https://www.oreplay.es/api/v1/events/${eventId}`, 'event-detail')
            isStatus200(`https://www.oreplay.es/api/v1/events/${eventId}/stages/${stageId}/classes`, 'stage-classes')
            isStatus200(`https://www.oreplay.es/api/v1/events/${eventId}/stages/${stageId}/clubs`, 'stage-clubs')
            isStatus200(`https://www.oreplay.es/api/v1/events/${eventId}`, 'event-detail')
            isStatus200(`https://www.oreplay.es/api/v1/events/${eventId}/stages/${stageId}/results?class_id=cfb6a1b4-1126-463f-baa1-73aaa126dcbc&forceSameDay=true`, 'results')
            isStatus200(`https://www.oreplay.es/api/v1/events/${eventId}/stages/${stageId}/results?class_id=da21506c-4dea-4a0b-80ef-160fb826173c&forceSameDay=true`, 'results')
            stageId = '36b5ca78-a212-4fe1-b31f-d8d4ef652ed7'
            isStatus200(`https://www.oreplay.es/api/v1/events/${eventId}`, 'event-detail')
            isStatus200(`https://www.oreplay.es/api/v1/events/${eventId}/stages/${stageId}/classes`, 'stage-classes')
            isStatus200(`https://www.oreplay.es/api/v1/events/${eventId}/stages/${stageId}/clubs`, 'stage-clubs')
            isStatus200(`https://www.oreplay.es/api/v1/events/${eventId}`, 'event-detail')
            isStatus200(`https://www.oreplay.es/api/v1/events/${eventId}/stages/${stageId}/results?class_id=19b5f4a4-6836-47a3-bce7-2b9c8da52fce&forceSameDay=true`, 'results')
            isStatus200(`https://www.oreplay.es/api/v1/events/${eventId}/stages/${stageId}/results?class_id=d42e8c49-159c-451e-bbcd-34f7832a99f9&forceSameDay=true`, 'results')
        }
    }

    sleep(1) // wait before next iteration
}
