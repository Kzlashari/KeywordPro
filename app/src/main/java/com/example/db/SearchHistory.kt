package com.example.db

import androidx.room.Dao
import androidx.room.Database
import androidx.room.Entity
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.PrimaryKey
import androidx.room.Query
import androidx.room.RoomDatabase
import kotlinx.coroutines.flow.Flow

@Entity(tableName = "search_history")
data class SearchHistoryEntity(
    @PrimaryKey(autoGenerate = true) val id: Int = 0,
    val keyword: String,
    val timestamp: Long = System.currentTimeMillis()
)

@Dao
interface SearchHistoryDao {
    @Query("SELECT * FROM search_history ORDER BY timestamp DESC LIMIT 15")
    fun getAllHistory(): Flow<List<SearchHistoryEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertSearch(search: SearchHistoryEntity)

    @Query("DELETE FROM search_history WHERE id = :id")
    suspend fun deleteHistory(id: Int)

    @Query("DELETE FROM search_history")
    suspend fun clearAll()
}

@Database(entities = [SearchHistoryEntity::class], version = 1, exportSchema = false)
abstract class SearchHistoryDatabase : RoomDatabase() {
    abstract fun searchHistoryDao(): SearchHistoryDao
}
