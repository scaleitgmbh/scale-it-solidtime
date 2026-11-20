import { mount } from '@vue/test-utils';
import { beforeAll, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import dayjs from 'dayjs';
import TimeEntryEditModal from './TimeEntryEditModal.vue';
import DatePicker from '@/packages/ui/src/Input/DatePicker.vue';
import type { TimeEntry } from '@/packages/api/src';

const TZ = 'Europe/Belgrade';

beforeAll(() => {
    (window as unknown as { getTimezoneSetting: () => string }).getTimezoneSetting = () => TZ;
});

function timeEntry(start: string, end: string): TimeEntry {
    return {
        id: 'te-1',
        description: 'Work',
        start,
        end,
        duration: dayjs(end).diff(dayjs(start), 'second'),
        project_id: null,
        task_id: null,
        organization_id: 'org-1',
        user_id: 'user-1',
        tags: [],
        billable: false,
        type: 'work',
    } as TimeEntry;
}

function mountModal(entry: TimeEntry) {
    const updateTimeEntry = vi.fn().mockResolvedValue(undefined);
    const wrapper = mount(TimeEntryEditModal, {
        props: {
            show: true,
            timeEntry: entry,
            enableEstimatedTime: false,
            updateTimeEntry,
            deleteTimeEntry: vi.fn(),
            createClient: vi.fn(),
            createProject: vi.fn(),
            createTag: vi.fn(),
            tags: [],
            projects: [],
            tasks: [],
            clients: [],
            currency: 'EUR',
            organizationBillableRate: null,
            canCreateProject: false,
        },
        global: {
            stubs: {
                DialogModal: {
                    template: '<div><slot name="content" /><slot name="footer" /></div>',
                },
                DatePicker: true,
                TimePickerSimple: true,
                DurationHumanInput: true,
                TimeTrackerProjectTaskDropdown: true,
                TagDropdown: true,
                Select: true,
            },
        },
    });
    return { wrapper, updateTimeEntry };
}

async function pickStartDate(wrapper: ReturnType<typeof mountModal>['wrapper'], wall: string) {
    const [startDate] = wrapper.findAllComponents(DatePicker);
    // DatePicker emits a local ISO string with the new date and the old time of day
    startDate!.vm.$emit('update:modelValue', dayjs.tz(wall, TZ).format());
    await nextTick();
    await nextTick();
}

async function save(wrapper: ReturnType<typeof mountModal>['wrapper']) {
    const button = wrapper.findAll('button').find((b) => b.text() === 'Update Time Entry');
    await button!.trigger('click');
}

describe('TimeEntryEditModal', () => {
    it('saves the moved entry with its times and duration intact', async () => {
        // 2026-11-18 09:15–11:00 Europe/Belgrade (UTC+1)
        const { wrapper, updateTimeEntry } = mountModal(
            timeEntry('2026-11-18T08:15:00Z', '2026-11-18T10:00:00Z')
        );
        await pickStartDate(wrapper, '2026-11-20T09:15:00');
        await save(wrapper);

        const saved = updateTimeEntry.mock.calls[0]![0] as TimeEntry;
        expect(saved.start).toBe('2026-11-20T08:15:00Z');
        expect(saved.end).toBe('2026-11-20T10:00:00Z');
    });

    it('keeps the 3h of an entry crossing midnight', async () => {
        // 2026-11-18 22:00 – 2026-11-19 01:00 local
        const { wrapper, updateTimeEntry } = mountModal(
            timeEntry('2026-11-18T21:00:00Z', '2026-11-19T00:00:00Z')
        );
        await pickStartDate(wrapper, '2026-11-25T22:00:00');
        await save(wrapper);

        const saved = updateTimeEntry.mock.calls[0]![0] as TimeEntry;
        expect(saved.start).toBe('2026-11-25T21:00:00Z');
        expect(saved.end).toBe('2026-11-26T00:00:00Z');
    });
});
