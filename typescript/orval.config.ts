import { defineConfig } from 'orval'

const name = 'v1api'

export default defineConfig({
  v1api: {
    output: {
      namingConvention: 'camelCase',
      target: 'infrastructure/repositories/' + name + '.ts',
      schemas: 'domain/types/' + name,
      mode: 'tags-split',
      // mock: {
      //   type: 'msw',
      //   indexMockFiles: true
      // },
      prettier: true,
      client: 'react-query',
      override: {
        query: {
          useQuery: true,
          usePrefetch: true
        },
        mutator: {
          path: './infrastructure/orval/orval-axios-instance.ts',
          name: 'orvalAxiosInstance'
        }
      }
    },
    input: {
      target: './' + name + '.yaml'
    }
  }
})
