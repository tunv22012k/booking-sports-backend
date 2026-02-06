<?php

namespace App\Repositories;

use App\Models\Venue;

class VenueRepository extends BaseRepository
{
    public function getModel()
    {
        return Venue::class;
    }

    public function getVenues($filters, $limit = null)
    {
        $query = $this->model->query();

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }
        
        // Type filter
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Location-based Sorting (PostGIS)
        if (!empty($filters['lat']) && !empty($filters['lng'])) {
            $lat = $filters['lat'];
            $lng = $filters['lng'];

            // Use PostGIS for distance (in KM) and sorting
            // ST_MakePoint(lng, lat) because PostGIS uses (x, y) order
            $userPoint = "ST_SetSRID(ST_MakePoint(?, ?), 4326)"; // User location (Geometry)
            
            // Calculate distance using optimized 'coordinates' column (Geography type)
            // 'coordinates' is Geography, so ST_Distance returns Meters by default.
            $query->selectRaw("*, (ST_Distance(coordinates, $userPoint::geography) / 1000) as distance", [$lng, $lat])
                  ->orderByRaw("coordinates <-> $userPoint", [$lng, $lat]);
        }

        // Optimization for Map View (select subset of fields)
        if (isset($filters['view']) && $filters['view'] === 'map') {
            $query->select(['id', 'name', 'type', 'lat', 'lng', 'address', 'price', 'pricing_type', 'image', 'rating', 'total_reviews', 'description']);
        } else {
            // Eager load relations for List/Detail view
            $query->with(['courts', 'extras', 'reviews']);
        }
        
        if ($limit) {
            return $query->paginate($limit);
        }

        return $query->get();
    }

    public function findWithRelations($id, $relations = [])
    {
        return $this->model->with($relations)->findOrFail($id);
    }
}
